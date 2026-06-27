<?php
namespace App\Models\Payroll;

use App\Models\BaseModel;
use App\Models\Identity\User;

class EmployeeSalary extends BaseModel
{
    protected $table = 'employee_salaries';
    protected $fillable = ['employee_user_id', 'template_id', 'is_active', 'effective_from'];
    protected $casts = ['is_active' => 'boolean', 'effective_from' => 'date'];

    public function employee() { return $this->belongsTo(User::class, 'employee_user_id'); }
    public function template() { return $this->belongsTo(SalaryTemplate::class, 'template_id'); }
}