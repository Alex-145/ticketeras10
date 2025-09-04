<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TicketStatusChanged implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public string $from,
        public string $to,
        public int $byUserId,
        public string $byName
    ) {
        Log::channel('realtime')->info('Broadcast:init', [
            'event'     => static::class,
            'ticket_id' => $ticket->id,
            'from'      => $from,
            'to'        => $to,
            'byUserId'  => $byUserId,
            'byName'    => $byName,
        ]);
    }

    public function broadcastOn(): Channel
    {
        Log::channel('realtime')->info('Broadcast:channel', ['name' => 'tickets']);
        return new Channel('tickets'); // si luego quieres privado: PrivateChannel
    }

    public function broadcastAs(): string
    {
        return 'TicketStatusChanged';
    }

    public function broadcastWith(): array
    {
        $payload = [
            'id'       => $this->ticket->id,
            'from'     => $this->from,
            'to'       => $this->to,
            'byUserId' => $this->byUserId,
            'byName'   => $this->byName,
        ];

        Log::channel('realtime')->info('Broadcast:payload', $payload);
        return $payload;
    }
}
