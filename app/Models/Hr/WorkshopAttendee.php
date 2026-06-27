<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Identity\User;

class WorkshopAttendee extends BaseModel
{
    protected $table = 'workshop_attendees';
    protected $fillable = ['workshop_id', 'employee_user_id', 'status', 'registered_at'];
    protected $casts = ['registered_at' => 'datetime'];

    public function workshop() { return $this->belongsTo(Workshop::class, 'workshop_id'); }
    public function employee() { return $this->belongsTo(User::class, 'employee_user_id'); }
}