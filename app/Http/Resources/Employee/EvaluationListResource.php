<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class EvaluationListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'period_start'   => Carbon::parse($this->period_start)->format('Y-m-d'),
            'period_end'     => Carbon::parse($this->period_end)->format('Y-m-d'),
            'overall_score'  => (float) $this->overall_score,
            'status'         => $this->status,
            'is_read'        => !is_null($this->read_at),
            'supervisor_name'=> $this->whenLoaded('supervisor', fn() => $this->supervisor->userProfile?->full_name),
        ];
    }
}