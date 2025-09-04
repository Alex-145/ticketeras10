<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicantAlias extends Model
{
    protected $fillable = ['applicant_id', 'alias'];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}
