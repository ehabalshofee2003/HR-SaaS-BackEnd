<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\Organization\Company;

class Task extends BaseModel
{
    protected $table = 'tasks';
    protected $fillable = [
        'company_id', 'employee_user_id', 'supervisor_user_id', 'template_id',
        'title', 'description', 'type', 'due_date', 'status', 'completed_at', 'reward_amount'
    ];
    protected $casts = ['due_date' => 'datetime', 'completed_at' => 'datetime'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function employee() { return $this->belongsTo(User::class, 'employee_user_id'); }
    public function supervisor() { return $this->belongsTo(User::class, 'supervisor_user_id'); }
    public function template() { return $this->belongsTo(TaskTemplate::class, 'template_id'); }
}