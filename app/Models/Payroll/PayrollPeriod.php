<?php
namespace App\Models\Payroll;

use App\Models\BaseModel;
use App\Models\Organization\Company;

class PayrollPeriod extends BaseModel
{
    protected $table = 'payroll_periods';
    protected $fillable = ['company_id', 'month', 'year', 'start_date', 'end_date', 'status'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function records() { return $this->hasMany(PayrollRecord::class, 'period_id'); }
}