<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // استبدل criterion بـ criteria
            'criteria_name' => $this->whenLoaded('criteria', fn() => $this->criteria->name),
            'weight'        => $this->whenLoaded('criteria', fn() => (float) $this->criteria->weight),
            'score'         => (float) $this->score,
            'comments'      => $this->comments,
        ];
    }
}