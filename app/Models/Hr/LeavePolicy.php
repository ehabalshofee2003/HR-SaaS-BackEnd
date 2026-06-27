<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Organization\Company;

class LeavePolicy extends BaseModel
{
    protected $table = 'leave_policies';
    protected $fillable = ['company_id', 'leave_type', 'days_per_year', 'is_carry_forward'];
    protected $casts = ['is_carry_forward' => 'boolean'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function balances() { return $this->hasMany(LeaveBalance::class, 'policy_id'); }
}