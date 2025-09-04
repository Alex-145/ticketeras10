<?php

namespace App\Services;

use App\Models\Applicant;
use App\Support\DbDialect;
use App\Support\TextUtils;
use Illuminate\Support\Facades\Log;

class ApplicantMatcher
{
    /**
     * 1) intenta por alias/nombre (normalizado y con score),
     * 2) si no, por teléfono (últimos 9/7/6 dígitos normalizando en SQL).
     */
    public function findByAliasNameOrPhone(?string $aliasOrName, ?string $phoneRaw): ?Applicant
    {
        $like = DbDialect::like();
        $norm = $aliasOrName ? TextUtils::normalizeName($aliasOrName) : null;

        // 1) Alias/Nombre
        if ($norm) {
            $words = TextUtils::words($norm);
            if ($words) {
                $cands = Applicant::query()
                    ->with('aliases')
                    ->where(function ($q) use ($like, $words) {
                        foreach ($words as $w) {
                            $q->orWhere('name', $like, "%{$w}%")
                                ->orWhereHas('aliases', fn($a) => $a->where('alias', $like, "%{$w}%"));
                        }
                    })
                    ->limit(50)
                    ->get();

                $best = null;
                $bestScore = 0.0;
                foreach ($cands as $c) {
                    $score = TextUtils::similarityScore($norm, TextUtils::normalizeName($c->name) ?? '');
                    foreach ($c->aliases as $al) {
                        $score = max($score, TextUtils::similarityScore($norm, TextUtils::normalizeName($al->alias) ?? ''));
                    }
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $best = $c;
                    }
                }

                Log::info('ApplicantMatcher@name_alias_scored', [
                    'candidates' => count($cands),
                    'best_id'    => $best?->id,
                    'best_score' => round($bestScore, 3),
                ]);

                if ($best && $bestScore >= 0.80) return $best;
            }
        }

        // 2) Teléfono
        if ($digits = TextUtils::digits($phoneRaw)) {
            $expr  = DbDialect::phoneDigitsExpr('phone');
            foreach ([9, 7, 6] as $n) {
                $tail = substr($digits, -$n);
                $match = Applicant::query()
                    ->whereRaw("$expr LIKE ?", ['%' . $tail . '%'])
                    ->first();
                if ($match) {
                    Log::info('ApplicantMatcher@phone_match', ['n' => $n, 'tail' => $tail, 'applicant_id' => $match->id]);
                    return $match;
                }
            }
        }

        Log::warning('ApplicantMatcher@not_found');
        return null;
    }
}
