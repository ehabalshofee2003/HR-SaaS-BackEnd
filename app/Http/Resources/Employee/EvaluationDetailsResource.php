<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use App\Http\Resources\Employee\EvaluationScoreResource;
use App\Models\Hr\EvaluationScore;
Use App\Models\Hr\PerformanceEvaluation;
Use App\Models\Hr\EvaluationCriteria;


class EvaluationDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'period_start'   => Carbon::parse($this->period_start)->format('Y-m-d'),
            'period_end'     => Carbon::parse($this->period_end)->format('Y-m-d'),
            'overall_score'  => (float) $this->overall_score,
            'status'         => $this->status,
            'notes'          => $this->notes,
            'is_read'        => !is_null($this->read_at),
            'read_at'        => $this->read_at ? Carbon::parse($this->read_at)->format('Y-m-d H:i:s') : null,
            'supervisor_name'=> $this->whenLoaded('supervisor', fn() => $this->supervisor->userProfile?->full_name),
            
            // إرجاع الدرجات المرتبطة مع اسم المعيار ووزنه
            'scores'         => EvaluationScoreResource::collection($this->whenLoaded('scores')),
        ];
    }
}