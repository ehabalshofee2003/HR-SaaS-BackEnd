<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\Organization\Company;
use App\Models\Organization\Department;

class Complaint extends BaseModel
{
    protected $table = 'complaints';
    protected $fillable = [
        'company_id', 'user_id', 'department_id', 'subject', 'description',
        'status', 'response', 'resolved_by', 'is_anonymous'
    ];
    protected $casts = ['is_anonymous' => 'boolean'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id'); }
    public function department() { return $this->belongsTo(Department::class, 'department_id'); }
    public function resolver() { return $this->belongsTo(User::class, 'resolved_by'); }
}