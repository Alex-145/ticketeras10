<?php

namespace App\Livewire\Tickets;

use App\Models\Applicant;
use App\Models\Ticket;
use App\Repositories\TicketRepository;
use App\Services\ApplicantMatcher;
use App\Services\OcrService;
use App\Services\TextExtractionService;
use App\Support\Concerns\HasTraceLogging;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Events\TicketStatusChanged;


#[Layout('layouts.app')]
class Board extends Component
{
    use WithFileUploads, HasTraceLogging;

    // Columnas
    public array $todo = [];
    public array $doing = [];
    public array $done = [];

    // Filtros/UI
    public string $search = '';
    public bool $showCreateModal = false;
    public string $createTab = 'image';

    // Pegado/imagen
    public $image;
    public ?string $extracted_applicant_name = null;
    public ?string $extracted_phone = null;
    public ?string $extracted_detail = null;
    public ?int $fallback_applicant_id = null;

    // Manual
    public ?int $applicant_id = null;
    public ?string $title = null;
    public ?string $description = null;
    public ?string $number = null;

    // DI manual vía container
    protected TicketRepository $tickets;
    protected OcrService $ocr;
    protected TextExtractionService $tex;
    protected ApplicantMatcher $matcher;

    public function boot(): void
    {
        $this->bootTrace(); // trait
        // resolver dependencias
        $this->tickets = app(TicketRepository::class);
        $this->ocr     = app(OcrService::class);
        $this->tex     = app(TextExtractionService::class);
        $this->matcher = app(ApplicantMatcher::class);
    }

    public function mount(): void
    {
        Log::info('Tickets.Board@mount', $this->ctx());
        $this->reload();
    }

    public function updatingSearch(): void
    {
        Log::info('Tickets.Board@updatingSearch', $this->ctx(['search' => $this->search]));
        $this->reload();
    }

    private function rt(array $extra = []): array
    {
        return array_merge([
            'comp'    => static::class,
            'trace'   => $this->traceId ?? 'n/a',
            'user_id' => auth()->id(),
        ], $extra);
    }

    private function reload(): void
    {
        Log::channel('realtime')->info('Board@reload:start', $this->rt(['search' => $this->search]));

        $query = Ticket::with(['applicant.company', 'claimedBy', 'lastMovedBy'])
            // ... tu filtro existente ...
            ->orderByDesc('id')
            ->get();

        $this->todo  = $query->where('status', 'todo')->values()->toArray();
        $this->doing = $query->where('status', 'doing')->values()->toArray();
        $this->done  = $query->where('status', 'done')->values()->toArray();

        Log::channel('realtime')->info('Board@reload:end', $this->rt([
            'counts' => ['todo' => count($this->todo), 'doing' => count($this->doing), 'done' => count($this->done)]
        ]));
    }

    // Imagen pegada/subida
    public function updatedImage(): void
    {
        if (!$this->image) {
            Log::warning('Tickets.Board@updatedImage: no image', $this->ctx());
            return;
        }

        $tmpPath = $this->image->store('tickets/tmp', 'public');
        Log::info('Tickets.Board@updatedImage:stored', $this->ctx(['tmp_path' => $tmpPath]));

        $full = storage_path('app/public/' . $tmpPath);
        $text = $this->ocr->extract($full) ?: basename($tmpPath);

        $data = $this->tex->extractAll($text);
        $this->extracted_applicant_name = $data['name'];
        $this->extracted_phone          = $data['phone'];
        $this->extracted_detail         = $data['detail'];

        Log::info('Tickets.Board@updatedImage:extracted', $this->ctx([
            'has_name'  => (bool)$this->extracted_applicant_name,
            'has_phone' => (bool)$this->extracted_phone,
            'len'       => $this->extracted_detail ? mb_strlen($this->extracted_detail) : 0,
        ]));
    }

    public function onPastedText(string $text): void
    {
        $data = $this->tex->extractAll($text);
        $this->extracted_applicant_name = $data['name'];
        $this->extracted_phone          = $data['phone'];
        $this->extracted_detail         = $data['detail'];

        Log::info('Tickets.Board@onPastedText', $this->ctx([
            'has_name'  => (bool)$this->extracted_applicant_name,
            'has_phone' => (bool)$this->extracted_phone,
            'len'       => $this->extracted_detail ? mb_strlen($this->extracted_detail) : 0,
        ]));
    }

    public function createFromImage(): void
    {
        Log::info('Tickets.Board@createFromImage:start', $this->ctx([
            'has_img'  => (bool)$this->image,
            'has_name' => (bool)$this->extracted_applicant_name,
            'has_phone' => (bool)$this->extracted_phone,
        ]));

        $this->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $applicant = $this->matcher->findByAliasNameOrPhone(
            $this->extracted_applicant_name,
            $this->extracted_phone
        ) ?: ($this->fallback_applicant_id ? Applicant::find($this->fallback_applicant_id) : null);

        if (!$applicant) {
            $this->dispatch('toast', type: 'error', message: 'Applicant not recognized. Select one manually.');
            return;
        }

        $stored = $this->image->store('tickets', 'public');

        Ticket::create([
            'description'  => $this->extracted_detail,
            'applicant_id' => $applicant->id,
            'company_id'   => $applicant->company_id,
            'module_id'    => null,
            'category_id'  => null,
            'status'       => 'todo',
            'image_path'   => $stored,
        ]);

        $this->resetCreateForm();
        $this->dispatch('toast', type: 'success', message: 'Ticket created from image.');
        $this->reload();
    }

    public function createManual(): void
    {
        $validated = $this->validate([
            'applicant_id' => ['required', 'exists:applicants,id'],
            'description'  => ['nullable', 'string', 'max:5000'],
        ]);

        $app = Applicant::with('company')->findOrFail($validated['applicant_id']);

        Ticket::create([
            'description'  => $validated['description'] ?? null,
            'applicant_id' => $app->id,
            'company_id'   => $app->company_id,
            'module_id'    => null,
            'category_id'  => null,
            'status'       => 'todo',
            'image_path'   => null,
        ]);

        $this->resetCreateForm();
        $this->dispatch('toast', type: 'success', message: 'Ticket created.');
        $this->reload();
    }
    public function refreshBoard(): void
    {
        Log::channel('realtime')->info('Board@refreshBoard', $this->rt());
        $this->reload();
    }
    public function moveTicket(int $ticketId, string $toStatus): void
    {
        Log::channel('realtime')->info('Board@moveTicket:start', $this->rt([
            'ticket_id' => $ticketId,
            'to' => $toStatus
        ]));

        if (!in_array($toStatus, ['todo', 'doing', 'done'], true)) {
            Log::channel('realtime')->warning('Board@moveTicket:invalid', $this->rt(['to' => $toStatus]));
            return;
        }

        $t = Ticket::findOrFail($ticketId);
        $from = $t->status;

        $t->last_moved_by = auth()->id();
        $t->last_moved_at = now();

        if ($toStatus === 'doing') {
            $t->claimed_by = auth()->id();
            $t->claimed_at = now();
        }

        $t->status = $toStatus;
        $t->save();

        Log::channel('realtime')->info('Board@moveTicket:saved', $this->rt([
            'ticket_id' => $t->id,
            'from' => $from,
            'to' => $toStatus
        ]));

        // Notifica a otros navegadores (Reverb)
        broadcast(new TicketStatusChanged(
            ticket: $t->fresh(['applicant.company', 'claimedBy', 'lastMovedBy']),
            from: $from,
            to: $toStatus,
            byUserId: auth()->id(),
            byName: auth()->user()?->name ?? 'Someone'
        ))->toOthers();

        Log::channel('realtime')->info('Board@moveTicket:broadcasted', $this->rt([
            'ticket_id' => $t->id
        ]));

        $this->reload();
    }
    public function clientEvent(string $type, array $payload = []): void
    {
        Log::channel('realtime')->info('ClientEvent', $this->rt([
            'type' => $type,
            'payload' => $payload,
        ]));
    }


    private function resetCreateForm(): void
    {
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

    public function render()
    {
        $applicants = Applicant::orderBy('name')->pluck('name', 'id');
        return view('livewire.tickets.board', compact('applicants'));
    }
}
