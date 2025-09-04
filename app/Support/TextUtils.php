<?php

namespace App\Support;

use Illuminate\Support\Str;

class TextUtils
{
    public static function normalizeName(?string $s): ?string
    {
        if ($s === null) return null;
        $s = Str::ascii($s);
        $s = mb_strtolower($s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim($s);
    }

    public static function words(string $s): array
    {
        $s = preg_replace('/[^a-z0-9\s]/u', ' ', self::normalizeName($s) ?? '');
        $parts = preg_split('/\s+/u', $s, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_filter($parts, fn($w) => mb_strlen($w) >= 2));
    }

    public static function digits(?string $s): ?string
    {
        if ($s === null) return null;
        $d = preg_replace('/\D+/', '', $s);
        return $d === '' ? null : $d;
    }

    /** 0..1 mezcla similar_text y Levenshtein */
    public static function similarityScore(string $a, string $b): float
    {
        similar_text($a, $b, $pct);
        $sim1 = $pct / 100.0;

        $len = max(mb_strlen($a), mb_strlen($b));
        if ($len === 0) return 1.0;
        $lev = levenshtein($a, $b);
        $sim2 = 1.0 - ($lev / $len);

        return max(0.0, min(1.0, ($sim1 * 0.6 + $sim2 * 0.4)));
    }
}
