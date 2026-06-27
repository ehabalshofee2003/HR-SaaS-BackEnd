<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Identity\User;

class LeaveBalance extends BaseModel
{
    protected $table = 'leave_balances';
    public $timestamps = true; // لا يوجد soft deletes
    protected $fillable = ['employee_user_id', 'policy_id', 'year', 'remaining_days'];

    public function employee() { return $this->belongsTo(User::class, 'employee_user_id'); }
    public function policy() { return $this->belongsTo(LeavePolicy::class, 'policy_id'); }
}