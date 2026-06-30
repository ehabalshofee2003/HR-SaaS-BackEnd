<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Organization\Company;

class PerformanceEvaluation extends Model
{
    use SoftDeletes;

    protected $table = 'performance_evaluations';

    protected $fillable = [
        'company_id', 'employee_user_id', 'supervisor_user_id',
        'period_start', 'period_end', 'overall_score', 'notes', 'status', 'read_at'
    ];

    protected $casts = [
        'period_start'  => 'date',
        'period_end'    => 'date',
        'overall_score' => 'decimal:2',
        'read_at'       => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(\App\Models\Identity\User::class, 'employee_user_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(\App\Models\Identity\User::class, 'supervisor_user_id');
    }

    public function scores()
    {
        return $this->hasMany(EvaluationScore::class, 'evaluation_id');
    }
    public function company() { return $this->belongsTo(Company::class, 'company_id'); }

}