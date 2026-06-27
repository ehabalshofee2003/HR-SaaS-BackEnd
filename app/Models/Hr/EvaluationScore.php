<?php
namespace App\Models\Hr;

use App\Models\BaseModel;

class EvaluationScore extends BaseModel
{
    protected $table = 'evaluation_scores';
    public $timestamps = true;
    protected $fillable = ['evaluation_id', 'criteria_id', 'score', 'comments'];

    public function evaluation() { return $this->belongsTo(PerformanceEvaluation::class, 'evaluation_id'); }
    public function criteria() { return $this->belongsTo(EvaluationCriteria::class, 'criteria_id'); }
}