<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $this->user_type,
            'status' => $this->status,
            
            // استخدام whenLoaded لمنع مشكلة N+1 Query
            'full_name' => $this->whenLoaded('profile', fn() => $this->profile->full_name),
            'avatar' => $this->whenLoaded('profile', fn() => $this->profile->avatar ? Storage::url($this->profile->avatar) : null),
            'national_id' => $this->whenLoaded('profile', fn() => $this->profile->national_id),
            
            // الحل الآمن: لف التاريخ بـ Carbon::parse
            'date_of_birth' => $this->whenLoaded('profile', fn() => $this->profile->date_of_birth ? Carbon::parse($this->profile->date_of_birth)->format('Y-m-d') : null),
            
            'job_title' => $this->whenLoaded('employeeDetail', fn() => $this->employeeDetail->job_title),
            'employment_status' => $this->whenLoaded('employeeDetail', fn() => $this->employeeDetail->employment_status),
            
            // الحل الآمن للـ hire_date
            'hire_date' => $this->whenLoaded('employeeDetail', fn() => $this->employeeDetail->hire_date ? Carbon::parse($this->employeeDetail->hire_date)->format('Y-m-d') : null),
            
            // الحل الآمن لـ created_at و updated_at
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s') : null,
        ];
    }
}