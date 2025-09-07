<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    protected $fillable = ['ticket_id', 'sender_type', 'sender_id', 'body'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
    public function attachments()
    {
        return $this->hasMany(TicketMessageAttachment::class, 'message_id');
    }

    // helpers
    public function isFromApplicant(): bool
    {
        return $this->sender_type === 'applicant';
    }
    public function isFromStaff(): bool
    {
        return $this->sender_type === 'staff';
    }
}
