<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
