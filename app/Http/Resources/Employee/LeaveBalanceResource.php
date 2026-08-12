<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $policy = $this->whenLoaded('policy') ? $this->policy : $this->policy;

        return [
            'id' => $this->id,
            'leave_type' => $policy?->leave_type, // annual, sick, emergency
            'leave_type_label' => $this->translateType($policy?->leave_type),
            'total_days' => (int) ($policy?->days_per_year ?? 0),
            'remaining_days' => (int) $this->remaining_days,
            'used_days' => (int) (($policy?->days_per_year ?? 0) - $this->remaining_days),
        ];
    }

private function translateType(?string $type): ?string
{
    return match ($type) {
        'annual' => 'Annual Leave',
        'sick' => 'Sick Leave',
        'emergency' => 'Emergency Leave',
        default => $type,
    };
}
}