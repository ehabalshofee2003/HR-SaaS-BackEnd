<?php
namespace App\Models\Payroll;

use App\Models\BaseModel;

class TemplateComponent extends BaseModel
{
    protected $table = 'template_components';
    protected $fillable = ['template_id', 'name', 'component_type', 'amount', 'is_percentage', 'calculation_base'];
    protected $casts = ['is_percentage' => 'boolean'];

    public function template() { return $this->belongsTo(SalaryTemplate::class, 'template_id'); }
}