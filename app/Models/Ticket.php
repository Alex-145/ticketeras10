<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'number',
        'title',
        'description',
        'applicant_id',
        'company_id',
        'module_id',
        'category_id',
        'status',
        'image_path',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // Rels
    public function applicant()
    {
        return $this->belongsTo(\App\Models\Applicant::class);
    }
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
    public function module()
    {
        return $this->belongsTo(\App\Models\Module::class);
    }
    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    // Accessor útil
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }
    // app/Models/Ticket.php
    public function claimedBy()
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }
    public function lastMovedBy()
    {
        return $this->belongsTo(User::class, 'last_moved_by');
    }
}
