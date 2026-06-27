<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\Organization\Branch;
use App\Models\Organization\Company;

class Workshop extends BaseModel
{
    protected $table = 'workshops';
    protected $fillable = [
        'company_id', 'branch_id', 'created_by', 'title', 'description',
        'location', 'start_date', 'end_date', 'capacity', 'status'
    ];
    protected $casts = ['start_date' => 'datetime', 'end_date' => 'datetime'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function branch() { return $this->belongsTo(Branch::class, 'branch_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function attendees() { return $this->hasMany(WorkshopAttendee::class, 'workshop_id'); }
}