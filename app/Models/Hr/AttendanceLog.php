<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\Organization\Branch;
use App\Models\Organization\Company;

class AttendanceLog extends BaseModel
{
    protected $table = 'attendance_logs';
    protected $fillable = [
        'company_id', 'employee_user_id', 'branch_id', 'qr_code_id', 
        'check_in', 'check_out', 'work_hours', 'type', 'status', 'notes', 'location',
        'reviewed_by_supervisor', 'reviewed_at_supervisor', 'reviewed_by_manager', 'reviewed_at_manager'
    ];
    protected $casts = [
        'check_in' => 'datetime', 'check_out' => 'datetime',
        'reviewed_at_supervisor' => 'datetime', 'reviewed_at_manager' => 'datetime'
    ];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function employee() { return $this->belongsTo(User::class, 'employee_user_id'); }
    public function branch() { return $this->belongsTo(Branch::class, 'branch_id'); }
    public function qrCode() { return $this->belongsTo(QrCode::class, 'qr_code_id'); }
    public function reviewerSupervisor() { return $this->belongsTo(User::class, 'reviewed_by_supervisor'); }
    public function reviewerManager() { return $this->belongsTo(User::class, 'reviewed_by_manager'); }
}