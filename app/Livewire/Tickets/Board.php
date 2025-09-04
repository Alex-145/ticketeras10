<?php

namespace App\Livewire\Tickets;

use App\Models\Ticket;
use App\Models\Applicant;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;

#[Layout('layouts.app')]
class Board extends Component
{
    use WithFileUploads;

    /** Columnas del board */
    public array $todo = [];
    public array $doing = [];
    public array $done = [];

    /** Búsqueda en el board */
    public string $search = '';

    /** Crear desde imagen (pegado / arrastre / file) */
    public $image; // upload temporal (Livewire)
    public ?string $extracted_applicant_name = null; // nombre/alias
    public ?string $extracted_phone = null;          // backup si no hay nombre/alias
    public ?string $extracted_detail = null;         // DETALLE (descripción)

    /** Fallback cuando no hay match automático */
    public ?int $fallback_applicant_id = null;

    /** Crear manual (si usas la pestaña manual) */
    public ?int $applicant_id = null;
    public ?string $title = null;        // no se guarda en createFromImage
    public ?string $description = null;  // manual
    public ?string $number = null;       // no se guarda en createFromImage

    /** UI */
    public bool $showCreateModal = false;
    public string $createTab = 'image'; // image | manual
    public ?int $showingTicketId = null;

    /** Trace para correlacionar logs por acción */
    public string $traceId;

    public function mount(): void
    {
        $this->traceId = (string) Str::uuid();
        Log::info('Tickets.Board@mount', $this->ctx());
        $this->reload();
    }

    /* ==========================================================
     * Helpers de compatibilidad y utilitarios
     * ========================================================== */

    /** LIKE case-insensitive según driver */
    private function likeOp(): string
    {
        return DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    /** Expresión SQL que devuelve solo dígitos del campo phone, según driver */
    private function phoneDigitsExpr(): string
    {
        // PostgreSQL: usa regexp_replace
        if (DB::getDriverName() === 'pgsql') {
            return "regexp_replace(COALESCE(phone,''), '\\D', '', 'g')";
        }

        // SQLite/MySQL: anidamos REPLACE para quitar los símbolos más comunes
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone,''),' ',''),'-',''),'+',''),'(',''),')','')";
    }

    /** Normaliza texto: minúsculas, sin tildes, espacios colapsados */
    private function normalizeName(?string $s): ?string
    {
        if ($s === null) return null;
        $s = Str::ascii($s);                  // quita tildes
        $s = mb_strtolower($s);               // a minúsculas
        $s = preg_replace('/\s+/u', ' ', $s); // colapsa espacios
        return trim($s);
    }

    /** Similaridad 0..1 combinando similar_text y Levenshtein normalizado */
    private function similarityScore(string $a, string $b): float
    {
        similar_text($a, $b, $pct);
        $sim1 = $pct / 100.0;

        $len = max(mb_strlen($a), mb_strlen($b));
        if ($len === 0) return 1.0;
        $lev = levenshtein($a, $b);
        $sim2 = 1.0 - ($lev / $len);

        return max(0.0, min(1.0, ($sim1 * 0.6 + $sim2 * 0.4)));
    }

    /** Divide en palabras útiles (>=2 chars) */
    private function words(string $s): array
    {
        $s = preg_replace('/[^a-z0-9\s]/u', ' ', $this->normalizeName($s) ?? '');
        $parts = preg_split('/\s+/u', $s, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_filter($parts, fn($w) => mb_strlen($w) >= 2));
    }

    private function digits(?string $s): ?string
    {
        if ($s === null) return null;
        $d = preg_replace('/\D+/', '', $s);
        return $d === '' ? null : $d;
    }

    /* =========================
     *  Carga de tarjetas (board)
     * ========================= */
    private function reload(): void
    {
        Log::info('Tickets.Board@reload:start', $this->ctx(['search' => $this->search]));
        $like = $this->likeOp();

        $query = Ticket::with(['applicant.company'])
            ->when($this->search !== '', function ($q) use ($like) {
                $term = '%' . $this->search . '%';
                $q->where(function ($w) use ($term, $like) {
                    $w->where('description', $like, $term)
                        ->orWhere('title', $like, $term)           // por si aún existe la col.
                        ->orWhere('number', $like, $term)          // idem
                        ->orWhereHas('applicant', fn($a) => $a->where('name', $like, $term))
                        ->orWhereHas('company', fn($c) => $c->where('name', $like, $term));
                });
            })
            ->orderByDesc('id')
            ->get();

        $this->todo  = $query->where('status', 'todo')->values()->toArray();
        $this->doing = $query->where('status', 'doing')->values()->toArray();
        $this->done  = $query->where('status', 'done')->values()->toArray();

        Log::info('Tickets.Board@reload:end', $this->ctx([
            'counts' => [
                'todo'  => count($this->todo),
                'doing' => count($this->doing),
                'done'  => count($this->done),
            ],
        ]));
    }

    public function updatingSearch(): void
    {
        Log::info('Tickets.Board@updatingSearch', $this->ctx(['search' => $this->search]));
        $this->reload();
    }

    /* =========================
     *  Pegado/Arrastre - Imagen
     * ========================= */

    /** Se disparará al seleccionar/pegar un archivo en $image */
    public function updatedImage(): void
    {
        if (!$this->image) {
            Log::warning('Tickets.Board@updatedImage: no image', $this->ctx());
            return;
        }

        // Guarda temporal
        $path = $this->image->store('tickets/tmp', 'public');
        $text = null;

        Log::info('Tickets.Board@updatedImage:stored', $this->ctx([
            'tmp_path' => $path,
            'mime'     => method_exists($this->image, 'getMimeType') ? $this->image->getMimeType() : null,
            'size'     => method_exists($this->image, 'getSize') ? $this->image->getSize() : null,
        ]));

        // OCR con wrapper si está instalado; si no, fallback al CLI
        try {
            $tessPath = config('services.tesseract.path', 'tesseract');
            $langsStr = (string) config('services.tesseract.langs', 'eng+spa');
            $langs = array_filter(explode('+', $langsStr));

            $version = @shell_exec('"' . $tessPath . '" --version');
            $hasWrapper = class_exists(TesseractOCR::class);

            Log::info('Tickets.Board@updatedImage:tesseract_info', $this->ctx([
                'wrapper_available' => $hasWrapper,
                'path'      => $tessPath,
                'version'   => $version ? trim($version) : null,
            ]));

            $full = storage_path('app/public/' . $path);

            if ($hasWrapper) {
                $ocr = (new TesseractOCR($full))
                    ->executable($tessPath)
                    ->oem(1) // LSTM
                    ->psm(6); // bloque de texto

                if (!empty($langs)) {
                    $ocr->lang(...$langs);
                }
                $text = $ocr->run();

                Log::info('Tickets.Board@updatedImage:ocr_done', $this->ctx([
                    'mode' => 'wrapper',
                    'text_len' => $text ? mb_strlen($text) : 0
                ]));
            } else {
                // Fallback CLI directo
                $tmpTxt = storage_path('app/' . uniqid('tess_', true));
                $cmd = '"' . $tessPath . '" "' . $full . '" "' . $tmpTxt . '" -l "' . $langsStr . '" --oem 1 --psm 6 2>&1';
                $out = shell_exec($cmd);
                Log::debug('Tickets.Board@updatedImage:tesseract_out', $this->ctx(['out' => $out]));

                $txtFile = $tmpTxt . '.txt';
                if (is_file($txtFile)) {
                    $text = file_get_contents($txtFile) ?: null;
                    @unlink($txtFile);
                    Log::info('Tickets.Board@updatedImage:ocr_done', $this->ctx([
                        'mode'     => 'cli',
                        'text_len' => $text ? mb_strlen($text) : 0
                    ]));
                }
            }
        } catch (\Throwable $e) {
            Log::error('Tickets.Board@updatedImage:ocr_error', $this->ctx(['error' => $e->getMessage()]));
            $text = null;
        }

        // Fallback: nombre de archivo si no hubo OCR
        if (!$text) {
            $text = basename($path);
            Log::info('Tickets.Board@updatedImage:fallback_filename', $this->ctx(['fallback_text' => $text]));
        }

        $this->extractFieldsFromText($text);
    }

    /** Llamado desde JS cuando se pega texto en la zona de pegado */
    public function onPastedText(string $text): void
    {
        Log::info('Tickets.Board@onPastedText', $this->ctx([
            'text_len'   => mb_strlen($text),
            'text_snip'  => mb_substr($text, 0, 160),
        ]));
        $this->extractFieldsFromText($text);
    }

    /** Heurísticas: obtenemos DETALLE + NOMBRE/ALIAS; teléfono solo como backup */
    private function extractFieldsFromText(string $text): void
    {
        $text = trim($text);

        // 1) Nombre o alias explícito tipo "Applicant: ..."
        $name = null;
        if (preg_match('/Applicant(?:\s*Name)?\s*[:\-]\s*([A-Za-zÁÉÍÓÚÑáéíóúñ\.\s]+)\b/u', $text, $m2)) {
            $name = trim($m2[1]);
        } else {
            // 2) Nombre heurístico: 2-4 palabras Capitalizadas
            if (preg_match('/\b([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+){1,3})\b/u', $text, $m3)) {
                $name = trim($m3[1]);
            }
        }
        $this->extracted_applicant_name = $name ?: null;

        // 3) Teléfono — solo backup
        $phone = null;
        if (preg_match('/(\+?\d{1,4})?[\s\-\.]?\d(?:[\s\-\.]?\d){6,}/', $text, $mPhone)) {
            $phone = trim($mPhone[0]);
        }
        if (!$phone && preg_match('/(?:\+?51)?\s*\(?9\)?[\s\-\.]?\d{3}[\s\-\.]?\d{3}[\s\-\.]?\d{3}/', $text, $mPE)) {
            $phone = trim($mPE[0]);
        }
        $this->extracted_phone = $phone ?: null;

        // 4) DETALLE: limpiamos ruido básico, colapsamos espacios y limitamos tamaño
        $detail = $this->makeDetailFromText($text, $name, $phone);
        $this->extracted_detail = $detail;

        Log::info('Tickets.Board@extractFieldsFromText:extracted', $this->ctx([
            'name'        => $this->extracted_applicant_name,
            'phone'       => $this->extracted_phone,
            'detail_len'  => $this->extracted_detail ? mb_strlen($this->extracted_detail) : 0,
            'detail_snip' => $this->extracted_detail ? mb_substr($this->extracted_detail, 0, 160) : null,
        ]));
    }

    /** Construye el detalle removiendo el nombre/alias y teléfono detectados, normaliza y corta */
    private function makeDetailFromText(string $text, ?string $name, ?string $phone): ?string
    {
        $original = $text;

        // Quita líneas muy cortas de encabezados típicos
        $lines = preg_split('/\R/u', $text);
        $lines = array_values(array_filter($lines, function ($ln) {
            $t = trim($ln);
            return $t !== '' && !preg_match('/^(From|Para|De|Asunto|Subject)[:\-]/i', $t);
        }));
        $text = implode("\n", $lines);

        // Quita el nombre detectado
        if ($name) {
            $nameQuoted = preg_quote($name, '/');
            $text = preg_replace('/' . $nameQuoted . '/u', '', $text);
        }

        // Quita el teléfono detectado
        if ($phone) {
            $phoneQuoted = preg_quote($phone, '/');
            $text = preg_replace('/' . $phoneQuoted . '/', '', $text);
        }

        // Limpia múltiple espacio y saltos
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);
        $text = trim($text);

        // Si quedó casi vacío, usa el original
        if ($text === '' || mb_strlen($text) < 10) {
            $text = trim($original);
        }

        // Evita guardar solo nombre de archivo de imagen
        if (preg_match('/\.(png|jpe?g|webp|gif)$/i', $text) && mb_strlen($text) < 40) {
            $text = null;
        }

        // Limita a 2000 chars
        if ($text && mb_strlen($text) > 2000) {
            $text = mb_substr($text, 0, 2000) . '…';
        }

        return $text ?: null;
    }

    /* =========================
     *  Crear ticket
     * ========================= */

    /** Crea ticket usando imagen + (detalle + applicant por nombre/alias; si no, por teléfono) */
    public function createFromImage(): void
    {
        Log::info('Tickets.Board@createFromImage:start', $this->ctx([
            'extracted_name'   => $this->extracted_applicant_name,
            'extracted_phone'  => $this->extracted_phone,
            'detail_len'       => $this->extracted_detail ? mb_strlen($this->extracted_detail) : 0,
            'has_image'        => (bool) $this->image,
            'fallback_applicant_id' => $this->fallback_applicant_id,
        ]));

        $this->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        // 1) Buscar por alias/nombre primero; si falla, por teléfono
        $applicant = $this->findApplicantByAliasOrNameFirst(
            $this->extracted_applicant_name,
            $this->extracted_phone
        );

        // 2) Fallback manual
        if (!$applicant && $this->fallback_applicant_id) {
            $applicant = Applicant::find($this->fallback_applicant_id);
            Log::info('Tickets.Board@createFromImage:fallback_applicant_used', $this->ctx([
                'fallback_applicant_id' => $this->fallback_applicant_id,
                'found' => (bool) $applicant,
            ]));
        }

        if (!$applicant) {
            Log::warning('Tickets.Board@createFromImage:applicant_not_found', $this->ctx());
            $this->dispatch('toast', type: 'error', message: 'Applicant not recognized. Pick one from the dropdown or create manually.');
            return;
        }

        $storedPath = $this->image->store('tickets', 'public');

        // SOLO guardamos: imagen, applicant, detalle.
        $ticket = Ticket::create([
            'description'  => $this->extracted_detail,
            'applicant_id' => $applicant->id,
            'company_id'   => $applicant->company_id,
            'module_id'    => null,
            'category_id'  => null,
            'status'       => 'todo',
            'image_path'   => $storedPath,
        ]);

        Log::info('Tickets.Board@createFromImage:created', $this->ctx([
            'ticket_id'    => $ticket->id,
            'image_path'   => $storedPath,
            'applicant_id' => $applicant->id,
            'company_id'   => $applicant->company_id,
            'detail_len'   => $this->extracted_detail ? mb_strlen($this->extracted_detail) : 0,
        ]));

        $this->resetCreateForm();
        $this->dispatch('toast', type: 'success', message: 'Ticket created from image.');
        $this->reload();
    }

    /** Crea ticket manual (sólo applicant + detail) */
    public function createManual(): void
    {
        Log::info('Tickets.Board@createManual:start', $this->ctx([
            'applicant_id' => $this->applicant_id,
            'title'        => $this->title,
            'number'       => $this->number,
        ]));

        $validated = $this->validate([
            'applicant_id' => ['required', 'exists:applicants,id'],
            'title'        => ['nullable', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:5000'],
            'number'       => ['nullable', 'string', 'max:50'],
        ]);

        $applicant = Applicant::with('company')->findOrFail($validated['applicant_id']);

        $ticket = Ticket::create([
            'description'  => $validated['description'] ?? null,
            'applicant_id' => $applicant->id,
            'company_id'   => $applicant->company_id,
            'module_id'    => null,
            'category_id'  => null,
            'status'       => 'todo',
            'image_path'   => null,
        ]);

        Log::info('Tickets.Board@createManual:created', $this->ctx([
            'ticket_id'    => $ticket->id,
            'applicant_id' => $applicant->id,
            'company_id'   => $applicant->company_id,
        ]));

        $this->resetCreateForm();
        $this->dispatch('toast', type: 'success', message: 'Ticket created.');
        $this->reload();
    }

    private function resetCreateForm(): void
    {
        Log::info('Tickets.Board@resetCreateForm', $this->ctx());

        $this->image = null;
        $this->extracted_applicant_name = null;
        $this->extracted_phone = null;
        $this->extracted_detail = null;

        $this->fallback_applicant_id = null;

        $this->applicant_id = null;
        $this->title = null;
        $this->description = null;
        $this->number = null;

        $this->showCreateModal = false;
        $this->createTab = 'image';
        $this->traceId = (string) Str::uuid();
    }

    /* =========================
     *  Drag & Drop
     * ========================= */

    public function moveTicket(int $ticketId, string $toStatus): void
    {
        Log::info('Tickets.Board@moveTicket:start', $this->ctx([
            'ticket_id' => $ticketId,
            'to'        => $toStatus,
        ]));

        if (!in_array($toStatus, ['todo', 'doing', 'done'], true)) {
            Log::warning('Tickets.Board@moveTicket:invalid_status', $this->ctx(['to' => $toStatus]));
            return;
        }

        $t = Ticket::findOrFail($ticketId);
        $from = $t->status;
        $t->status = $toStatus;
        $t->save();

        Log::info('Tickets.Board@moveTicket:done', $this->ctx([
            'ticket_id' => $ticketId,
            'from'      => $from,
            'to'        => $toStatus,
        ]));

        $this->reload();
    }

    /* =========================
     *  Matching: nombre/alias primero; si no, teléfono
     * ========================= */

    /**
     * Orden solicitado:
     * 1) alias -> 2) nombre -> 3) teléfono (sufijo 9/7 dígitos, normalizando en SQL).
     */
    private function findApplicantByAliasOrNameFirst(?string $aliasOrName, ?string $phoneRaw): ?Applicant
    {
        Log::info('Tickets.Board@findApplicantByAliasOrNameFirst:start', $this->ctx([
            'aliasOrName' => $aliasOrName,
            'phoneRaw'    => $phoneRaw,
        ]));

        $like   = $this->likeOp();
        $needle = $aliasOrName ? trim($aliasOrName) : null;
        $norm   = $this->normalizeName($needle);

        // 1) Alias/Nombre con normalización y scoring
        if ($norm) {
            $words = $this->words($norm);
            if (!empty($words)) {
                $candidates = Applicant::query()
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

                foreach ($candidates as $cand) {
                    $candName = $this->normalizeName($cand->name);
                    $score = $this->similarityScore($norm, $candName ?? '');

                    foreach ($cand->aliases as $al) {
                        $aliasNorm = $this->normalizeName($al->alias);
                        $score = max($score, $this->similarityScore($norm, $aliasNorm ?? ''));
                    }

                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $best = $cand;
                    }
                }

                Log::info('Tickets.Board@findApplicantByAliasOrNameFirst:alias_name_scored', $this->ctx([
                    'candidates' => count($candidates),
                    'best_id'    => $best?->id,
                    'best_score' => round($bestScore, 3),
                ]));

                if ($best && $bestScore >= 0.80) {
                    return $best;
                }
            }
        }

        // 2) TELÉFONO (backup) — normaliza en SQL para ignorar espacios, +, (), -
        if ($phoneDigits = $this->digits($phoneRaw)) {
            $expr  = $this->phoneDigitsExpr(); // expresión SQL para solo-dígitos
            $tail9 = substr($phoneDigits, -9);
            $tail7 = substr($phoneDigits, -7);

            // últimos 9
            $byPhone = Applicant::query()
                ->whereRaw("$expr LIKE ?", ['%' . $tail9 . '%'])
                ->first();

            if ($byPhone) {
                Log::info('Tickets.Board@findApplicantByAliasOrNameFirst:match_phone_tail9', $this->ctx([
                    'phone_digits' => $phoneDigits,
                    'tail9'        => $tail9,
                    'applicant_id' => $byPhone->id,
                ]));
                return $byPhone;
            }

            // últimos 7
            $byPhone7 = Applicant::query()
                ->whereRaw("$expr LIKE ?", ['%' . $tail7 . '%'])
                ->first();

            if ($byPhone7) {
                Log::info('Tickets.Board@findApplicantByAliasOrNameFirst:match_phone_tail7', $this->ctx([
                    'phone_digits' => $phoneDigits,
                    'tail7'        => $tail7,
                    'applicant_id' => $byPhone7->id,
                ]));
                return $byPhone7;
            }

            // (Opcional) últimos 6 por si hay extensiones o ruido
            $tail6 = substr($phoneDigits, -6);
            $byPhone6 = Applicant::query()
                ->whereRaw("$expr LIKE ?", ['%' . $tail6 . '%'])
                ->first();

            if ($byPhone6) {
                Log::info('Tickets.Board@findApplicantByAliasOrNameFirst:match_phone_tail6', $this->ctx([
                    'phone_digits' => $phoneDigits,
                    'tail6'        => $tail6,
                    'applicant_id' => $byPhone6->id,
                ]));
                return $byPhone6;
            }
        }

        Log::warning('Tickets.Board@findApplicantByAliasOrNameFirst:not_found', $this->ctx());
        return null;
    }

    /* =========================
     *  Render
     * ========================= */

    public function render()
    {
        $applicants = Applicant::orderBy('name')->pluck('name', 'id');
        return view('livewire.tickets.board', compact('applicants'));
    }

    /* =========================
     *  Helper de contexto para logs
     * ========================= */
    private function ctx(array $extra = []): array
    {
        return array_merge([
            'comp'    => 'tickets.board',
            'trace'   => $this->traceId ?? 'n/a',
            'user_id' => auth()->id(),
        ], $extra);
    }
}
