<?php

namespace App\Http\Resources\Supervisor;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EmployeeListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'job_title' => $this->job_title,
            'basic_salary' => $this->basic_salary,
            'contract_type' => $this->contract_type,
            'employment_status' => $this->employment_status,
            'avatar_url' => $this->avatar ? Storage::url($this->avatar) : null,
            
            // حالة الحضور اليوم (نستخدم Carbon::parse للسلامة)
            'today_attendance_status' => $this->today_status ? Carbon::parse($this->today_check_in)->format('H:i') . ' - ' . $this->today_status : 'Not Recorded',
            
            'pending_tasks_count' => $this->pending_tasks_count ?? 0,
            
            'created_at' => Carbon::parse($this->created_at)->format('Y-m-d H:i:s')
        ];
    }
}