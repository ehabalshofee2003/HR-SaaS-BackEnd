<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Organization\Company;

class EvaluationCriteria extends BaseModel
{
    protected $table = 'evaluation_criteria';
    protected $fillable = ['company_id', 'name', 'description', 'weight', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function scores() { return $this->hasMany(EvaluationScore::class, 'criteria_id'); }
}