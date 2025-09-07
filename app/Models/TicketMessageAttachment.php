<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessageAttachment extends Model
{
    protected $fillable = ['message_id', 'path', 'original_name', 'mime', 'size', 'width', 'height'];
    public function message()
    {
        return $this->belongsTo(TicketMessage::class);
    }

    public function url(): string
    {
        return asset('storage/' . $this->path);
    }
}
