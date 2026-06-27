<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\Organization\Company;

class LeaveRequest extends BaseModel
{
    protected $table = 'leave_requests';
  // هذه المصفوفة هي السبب! يجب أن تحتوي على كل الحقول القابلة للإدخال
    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'reason',
        'attachment',
        'status',
        'approver_id',
        'approved_at',
        'rejection_reason',
    ];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'approved_at' => 'datetime'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function employee() { return $this->belongsTo(User::class, 'employee_user_id'); }
    public function supervisor() { return $this->belongsTo(User::class, 'supervisor_user_id'); }
    public function leaveType() { return $this->belongsTo(LeaveType::class); }
    public function approver() { return $this->belongsTo(\App\Models\Identity\User::class, 'approver_id'); }
    public function rejecter() { return $this->belongsTo(User::class, 'rejected_by'); }
 
}