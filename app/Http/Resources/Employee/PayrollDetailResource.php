<?php

namespace App\Http\Resources\Employee;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'month' => $this->whenLoaded('period', fn() => $this->period->month),
            'year' => $this->whenLoaded('period', fn() => $this->period->year),
            'status' => $this->whenLoaded('period', fn() => $this->period->status),
            'gross_salary' => (float) $this->gross_salary,
            'total_bonuses' => (float) $this->total_bonuses,
            'total_deductions' => (float) $this->total_deductions,
            'net_salary' => (float) $this->net_salary,
            'paid_at' => $this->paid_at ? Carbon::parse($this->paid_at)->format('Y-m-d H:i:s') : null,
            'approved_at' => $this->approved_at ? Carbon::parse($this->approved_at)->format('Y-m-d H:i:s') : null,
            'details' => PayrollComponentResource::collection($this->whenLoaded('details')),
        ];
    }
}