<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $this->user_type,
            'status' => $this->status,
            
            'full_name' => $this->whenLoaded('profile', fn() => $this->profile->full_name),
            'avatar' => $this->whenLoaded('profile', fn() => $this->profile->avatar),
            'national_id' => $this->whenLoaded('profile', fn() => $this->profile->national_id),
            'date_of_birth' => $this->whenLoaded('profile', fn() => $this->profile->date_of_birth?->format('Y-m-d')),
            
            'job_title' => $this->whenLoaded('employeeDetail', fn() => $this->employeeDetail->job_title),
            'employment_status' => $this->whenLoaded('employeeDetail', fn() => $this->employeeDetail->employment_status),
            'hire_date' => $this->whenLoaded('employeeDetail', fn() => $this->employeeDetail->hire_date?->format('Y-m-d')),
        ];
    }
}