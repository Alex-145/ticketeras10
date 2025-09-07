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

    public function mount(Ticket $ticket)
    {
        $this->ticket = $ticket;
        // Autorización básica (owner applicant o staff con rol)
        $user = Auth::user();
        abort_unless(
            ($user?->hasRole('admin') || $user?->hasRole('agent'))
                || (\App\Models\Applicant::where('user_id', $user?->id)->value('id') === $ticket->applicant_id),
            403
        );
    }

    public function send()
    {
        $this->validate([
            'message' => ['nullable', 'string', 'max:5000'],
            'uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        ]);


        $user = Auth::user();
        $applicant = Applicant::where('user_id', $user->id)->first();

        $senderType = $applicant ? 'applicant' : 'staff';
        $senderId   = $applicant ? $applicant->id : $user->id;
        Log::info('Chat@send: creating message', [
            'ticket_id' => $this->ticket->id,
            'user_id'   => auth()->id(),
        ]);

        $msg = TicketMessage::create([
            'ticket_id'   => $this->ticket->id,
            'sender_type' => $senderType,
            'sender_id'   => $senderId,
            'body'        => $this->message ?: null,
        ]);

        Log::info('Chat@send: broadcasting event', [
            'message_id' => $msg->id,
            'socket_id'  => request()->header('X-Socket-Id'), // útil para ver si llega
        ]);

        foreach ($this->uploads as $file) {
            $path = $file->store('tickets/' . $this->ticket->id, 'public');
            TicketMessageAttachment::create([
                'message_id'    => $msg->id,
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime'          => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        // Broadcast
        broadcast(new TicketMessageCreated($msg)); // <- sin toOthers() por ahora para ver en ambos tabs

        // reset UI
        $this->message = '';
        $this->uploads = [];
        $this->dispatch('toast', type: 'success', message: 'Enviado');
    }

    // para recibir desde Echo
    public function messageArrived($payload)
    {
        // no necesitamos estado local; haremos lazy refresh
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $messages = $this->ticket->messages()
            ->with('attachments')
            ->orderBy('created_at')
            ->get();

        return view('livewire.tickets.chat', compact('messages'));
    }
}
