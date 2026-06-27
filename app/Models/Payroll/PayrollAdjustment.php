<?php
namespace App\Models\Payroll;

use App\Models\BaseModel;
use App\Models\Identity\User;

class PayrollAdjustment extends BaseModel
{
    protected $table = 'payroll_adjustments';
    protected $fillable = ['payroll_record_id', 'created_by', 'adjustment_type', 'amount', 'reason'];

    public function record() { return $this->belongsTo(PayrollRecord::class, 'payroll_record_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}