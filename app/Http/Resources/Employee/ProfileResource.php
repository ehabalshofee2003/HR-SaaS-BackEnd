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
        $profile = $this->profile;
        $employeeDetail = $this->employeeDetail;

        return [
            'id'                => $this->id,
            'full_name'         => $profile?->full_name,
            'phone'             => $this->phone,
            'email'             => $this->email,
            'user_type'         => $this->user_type,
            'status'            => $this->status,

            // الصورة: تُقرأ من علاقة profile، وليس من موديل User مباشرة
            'avatar_url'        => $profile?->avatar ? Storage::url($profile->avatar) : null,

            'national_id'       => $profile?->national_id,
            'date_of_birth'     => $profile?->date_of_birth ? Carbon::parse($profile->date_of_birth)->format('Y-m-d') : null,

            'job_title'         => $employeeDetail?->job_title,
            'employment_status' => $employeeDetail?->employment_status,
            'hire_date'         => $employeeDetail?->hire_date ? Carbon::parse($employeeDetail->hire_date)->format('Y-m-d') : null,

            'department_name'   => $employeeDetail?->department?->name,

            // المسار الصحيح: عبر supervisor_id المباشر على employee_details
            'supervisor_name'   => $employeeDetail?->supervisor?->profile?->full_name,

            'basic_salary'      => $employeeDetail?->basic_salary,

            'created_at'        => Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at'        => Carbon::parse($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}