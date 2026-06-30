<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkshopAttendee extends Model
{
    use SoftDeletes;

    protected $table = 'workshop_attendees';

    protected $fillable = [
        'workshop_id', 'employee_user_id', 'status', 'registered_at'
    ];

    protected $casts = [
        'registered_at' => 'datetime',
    ];

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\Identity\User::class, 'employee_user_id');
    }
}