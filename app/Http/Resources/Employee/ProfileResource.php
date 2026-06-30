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
    $employeeDetail = $this->whenLoaded('employeeDetail') ? $this->employeeDetail : $this->employeeDetail;
    
    return [
        'id'                => $this->id,
        'full_name'         => $this->whenLoaded('userProfile', fn() => $this->userProfile->full_name),
        'phone'             => $this->phone,
        'email'             => $this->email,
        'user_type'         => $this->user_type,
        'status'            => $this->status,
        
        // الصورة: إرجاع الرابط الكامل إذا وُجدت
        'avatar_url'        => $this->avatar ? Storage::url($this->avatar) : null,
        
        // البيانات الوظيفية مع Safe Navigation لتجنب الأخطاء إذا لم يكن للموظف تفاصيل
        'job_title'         => $employeeDetail?->job_title,
        'employment_status' => $employeeDetail?->employment_status,
        'hire_date'         => $employeeDetail?->hire_date ? \Carbon\Carbon::parse($employeeDetail->hire_date)->format('Y-m-d') : null,
        
        // الحقول المطلوبة للواجهة الجديدة:
        'department_name'   => $employeeDetail?->department?->name,
        
        // ملاحظة: إذا كان المدير المباشر مرتبطاً بقسم الموظف (كما هو شائع):
        'supervisor_name'   => $employeeDetail?->department?->manager?->userProfile?->full_name,
        
        // بديل للمدير المباشر: إذا كان هناك حقل supervisor_id مباشر في جدول employee_details
        // 'supervisor_name' => $employeeDetail?->supervisor?->userProfile?->full_name,
        
        // الراتب الأساسي (عدّل اسم الحقل 'basic_salary' إذا كان مختلفاً في الداتا بيز)
        'basic_salary'      => $employeeDetail?->basic_salary,
        
        'created_at'        => \Carbon\Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
        'updated_at'        => \Carbon\Carbon::parse($this->updated_at)->format('Y-m-d H:i:s'),
    ];
}
}