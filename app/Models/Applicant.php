<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Applicant extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'phone', 'company_id'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function aliases()
    {
        return $this->hasMany(\App\Models\ApplicantAlias::class);
    }
}
