<?php
namespace App\Models\Hr;

use App\Models\BaseModel;
use App\Models\Identity\User;
use App\Models\Organization\Company;

class PerformanceEvaluation extends BaseModel
{
    protected $table = 'performance_evaluations';
    protected $fillable = [
        'company_id', 'employee_user_id', 'supervisor_user_id',
        'period_start', 'period_end', 'overall_score', 'notes', 'status'
    ];
    protected $casts = ['period_start' => 'date', 'period_end' => 'date'];

    public function company() { return $this->belongsTo(Company::class, 'company_id'); }
    public function employee() { return $this->belongsTo(User::class, 'employee_user_id'); }
    public function supervisor() { return $this->belongsTo(User::class, 'supervisor_user_id'); }
    public function scores() { return $this->hasMany(EvaluationScore::class, 'evaluation_id'); }
}