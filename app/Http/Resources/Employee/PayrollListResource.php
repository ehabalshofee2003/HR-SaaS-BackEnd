<?php

namespace App\Http\Resources\Employee;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'month' => $this->whenLoaded('period', fn() => $this->period->month),
            'year' => $this->whenLoaded('period', fn() => $this->period->year),
            'status' => $this->whenLoaded('period', fn() => $this->period->status),
            'net_salary' => (float) $this->net_salary,
            'paid_at' => $this->paid_at ? Carbon::parse($this->paid_at)->format('Y-m-d H:i:s') : null,
        ];
    }
}