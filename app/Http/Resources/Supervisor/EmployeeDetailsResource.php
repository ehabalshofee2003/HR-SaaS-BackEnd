<?php

namespace App\Http\Resources\Supervisor;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EmployeeDetailsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'info' => [
                'id' => $this->id,
                'full_name' => $this->full_name,
                'phone' => $this->phone,
                'email' => $this->email,
                'avatar_url' => $this->avatar ? Storage::url($this->avatar) : null,
                'job_title' => $this->job_title,
                'basic_salary' => $this->basic_salary,
                'contract_type' => $this->contract_type,
                'employment_status' => $this->employment_status,
                'hire_date' => Carbon::parse($this->hire_date)->format('Y-m-d'),
                'national_id' => $this->national_id,
                'date_of_birth' => $this->date_of_birth ? Carbon::parse($this->date_of_birth)->format('Y-m-d') : null,
            ],
            'quick_stats' => [
                'last_net_salary' => $this->last_net_salary,
                'pending_tasks' => 0, // سنربطه لاحقاً
            ],
        ];
    }
}