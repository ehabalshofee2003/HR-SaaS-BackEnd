<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\Organization\Company;

class ExceptionRequest extends BaseModel
{
    protected $table = 'exception_requests';
    protected $fillable = [
        'company_id', 'employee_user_id', 'supervisor_user_id', 'type',
        'description', 'status', 'approved_by', 'approved_at'
    ];
    protected $casts = ['approved_at' => 'datetime'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function employee() { return $this->belongsTo(User::class, 'employee_user_id'); }
    public function supervisor() { return $this->belongsTo(User::class, 'supervisor_user_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}