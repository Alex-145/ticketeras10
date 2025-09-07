<?php

namespace App\Events;

use App\Models\TicketMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // <--- NOW
use Illuminate\Support\Facades\Log;

class TicketMessageCreated implements ShouldBroadcastNow
{
    public function __construct(public TicketMessage $message)
    {
        Log::info('TicketMessageCreated::__construct', [
            'message_id' => $message->id,
            'ticket_id'  => $message->ticket_id,
            'sender_type' => $message->sender_type,
            'sender_id'  => $message->sender_id,
        ]);
    }

    public function broadcastOn()
    {
        $channel = new PrivateChannel('tickets.' . $this->message->ticket_id);
        Log::info('TicketMessageCreated@broadcastOn', ['channel' => 'private-tickets.' . $this->message->ticket_id]);
        return $channel;
    }

    public function broadcastAs()
    {
        return 'TicketMessageCreated';
    }

    public function broadcastWith(): array
    {
        $payload = [
            'id'         => $this->message->id,
            'ticket_id'  => $this->message->ticket_id,
            'sender_type' => $this->message->sender_type,
            'sender_id'  => $this->message->sender_id,
            'body'       => $this->message->body,
            'created_at' => optional($this->message->created_at)->toISOString(),
        ];
        Log::info('TicketMessageCreated@broadcastWith', $payload);
        return $payload;
    }
}
