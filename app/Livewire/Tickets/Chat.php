<?php

namespace App\Livewire\Tickets;

use App\Events\TicketMessageCreated;
use App\Models\Applicant;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketMessageAttachment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;


#[Layout('layouts.app')]
class Chat extends Component
{
    use WithFileUploads;

    public Ticket $ticket;
    public string $message = '';
    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    // Meta editables solo para staff:
    public ?int $module_id = null;
    public ?int $category_id = null;
    public string $priority = 'normal';
    public string $kind = 'consulta';

    public function mount(Ticket $ticket)
    {
        $this->ticket = $ticket->loadMissing('applicant.company'); // 👈 traemos compañía del owner

        $user = auth()->user();

        // Applicant actual (si lo es)
        $meApplicant = \App\Models\Applicant::with('company')
            ->where('user_id', $user->id)
            ->first();

        $sameCompany = $meApplicant
            && $this->ticket->applicant
            && $meApplicant->company_id
            && $meApplicant->company_id === $this->ticket->applicant->company_id;

        $can =
            $user->hasAnyRole(['admin', 'agent']) // staff
            || $sameCompany;                     // applicants de la misma company

        abort_unless($can, 403);

        // precarga meta como ya lo tenías…
        $this->module_id   = $this->ticket->module_id;
        $this->category_id = $this->ticket->category_id;
        $this->priority    = $this->ticket->priority ?? 'normal';
        $this->kind        = $this->ticket->kind ?? 'consulta';
    }

    public function send()
    {
        $this->validate([
            'message' => ['nullable', 'string', 'max:5000'],
            'uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        ]);

        $user = auth()->user();
        $applicant = \App\Models\Applicant::where('user_id', $user->id)->first();
        $senderType = $applicant ? 'applicant' : 'staff';
        $senderId   = $applicant ? $applicant->id : $user->id;

        $msg = \App\Models\TicketMessage::create([
            'ticket_id'   => $this->ticket->id,
            'sender_type' => $senderType,
            'sender_id'   => $senderId,
            'body'        => $this->message ?: null,
        ]);

        foreach ($this->uploads as $file) {
            $path = $file->store('tickets/' . $this->ticket->id, 'public');
            \App\Models\TicketMessageAttachment::create([
                'message_id'    => $msg->id,
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime'          => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        // primera respuesta (si staff y aun no registrada)
        if ($senderType === 'staff' && is_null($this->ticket->first_response_at)) {
            $this->ticket->first_response_at = now();
            $this->ticket->save();
        }

        // broadcast (dejas ShouldBroadcastNow en el evento)
        broadcast(new \App\Events\TicketMessageCreated($msg))->toOthers();

        $this->message = '';
        $this->uploads = [];
        $this->dispatch('toast', type: 'success', message: 'Enviado');
    }

    public function saveMeta()
    {
        // Solo staff:
        abort_unless(auth()->user()->hasAnyRole(['admin', 'agent']), 403);

        $this->validate([
            'priority'    => ['required', 'in:low,normal,high,urgent'],
            'kind'        => ['required', 'in:error,consulta,capacitacion'],
            'module_id'   => ['nullable', 'integer', 'exists:modules,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $this->ticket->forceFill([
            'priority'    => $this->priority,
            'kind'        => $this->kind,
            'module_id'   => $this->module_id,
            'category_id' => $this->category_id,
            'last_moved_by' => auth()->id(),
            'last_moved_at' => now(),
        ])->save();

        $this->dispatch('toast', type: 'success', message: 'Metadatos actualizados.');
    }
    public function resolve()
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'agent']), 403);

        $this->ticket->forceFill([
            'status' => 'done',
            'last_moved_by' => auth()->id(),
            'last_moved_at' => now(),
        ])->save();

        // Si ya tienes un canal/evento para estados, dispara aquí (ejemplo):
        // broadcast(new \App\Events\TicketStatusChanged($this->ticket))->toOthers();

        $this->dispatch('toast', type: 'success', message: 'Ticket resuelto.');
    }
    // para recibir desde Echo
    public function messageArrived($payload)
    {
        $this->dispatch('$refresh');
    }


    public function render()
    {
        $messages = $this->ticket->messages()->with('attachments')->orderBy('created_at')->get();

        // métricas
        $firstResponseAt = $this->ticket->first_response_at
            ?? $this->ticket->messages()->where('sender_type', 'staff')->min('created_at');
        $elapsed = now()->diffForHumans($this->ticket->created_at, ['parts' => 3, 'short' => true]); // ej: 1h 3m

        return view('livewire.tickets.chat', [
            'messages' => $messages,
            'firstResponseAt' => $firstResponseAt,
            'elapsed' => $elapsed,
        ]);
    }
}
