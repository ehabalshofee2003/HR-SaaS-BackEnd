<?php
namespace App\Models\Payroll;

use App\Models\BaseModel;
use App\Models\Organization\Company;

class SalaryTemplate extends BaseModel
{
    protected $table = 'salary_templates';
    protected $fillable = ['company_id', 'name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function components() { return $this->hasMany(TemplateComponent::class, 'template_id'); }
    public function employeeSalaries() { return $this->hasMany(EmployeeSalary::class, 'template_id'); }
}