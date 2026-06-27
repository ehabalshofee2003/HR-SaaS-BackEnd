<?php
namespace App\Models\Payroll;

use App\Models\BaseModel;
use App\Models\Identity\User;

class PayrollRecord extends BaseModel
{
    protected $table = 'payroll_records';
    protected $fillable = [
        'employee_user_id', 'period_id', 'gross_salary', 'total_deductions',
        'total_bonuses', 'net_salary', 'status', 'approved_by', 'approved_at',
        'locked_at', 'paid_at'
    ];
    protected $casts = ['approved_at' => 'datetime', 'locked_at' => 'datetime', 'paid_at' => 'datetime'];

    public function employee() { return $this->belongsTo(User::class, 'employee_user_id'); }
    public function period() { return $this->belongsTo(PayrollPeriod::class, 'period_id'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function details() { return $this->hasMany(PayrollRecordDetail::class, 'record_id'); }
    public function adjustments() { return $this->hasMany(PayrollAdjustment::class, 'payroll_record_id'); }
}