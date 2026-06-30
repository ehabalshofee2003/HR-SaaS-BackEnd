<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Resources\Json\JsonResource;

class LeaveBalanceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            // نوع الإجازة مأخوذ من جدول السياسات المرتبط بالرصيد
            'leave_type' => $this->whenLoaded('policy', fn() => $this->policy->leave_type),
            'remaining_days' => (float) $this->remaining_days,
            'total_days_per_year' => $this->whenLoaded('policy', fn() => (float) $this->policy->days_per_year),
            'is_carry_forward' => $this->whenLoaded('policy', fn() => (bool) $this->policy->is_carry_forward),
            'year' => $this->year,
        ];
    }
}