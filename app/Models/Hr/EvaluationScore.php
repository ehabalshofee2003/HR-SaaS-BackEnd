<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Organization\Company;
class EvaluationScore extends Model
{
    protected $table = 'evaluation_scores';

    protected $fillable = [
        'evaluation_id', 'criteria_id', 'score', 'comments'
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

 
    public function evaluation() { return $this->belongsTo(PerformanceEvaluation::class, 'evaluation_id'); }
    public function criteria()
    {
        return $this->belongsTo(EvaluationCriteria::class, 'criteria_id');
    }
}