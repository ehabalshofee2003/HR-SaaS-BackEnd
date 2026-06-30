<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workshop extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'created_by', 'title', 'description',
        'location', 'start_date', 'end_date', 'capacity', 'status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'capacity'   => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Organization\Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Organization\Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Identity\User::class, 'created_by');
    }

    public function attendees()
    {
        return $this->hasMany(WorkshopAttendee::class, 'workshop_id');
    }
}