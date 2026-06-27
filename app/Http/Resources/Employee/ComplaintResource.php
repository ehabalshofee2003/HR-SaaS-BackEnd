<?php

namespace App\Http\Resources\Employee;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'company_id'    => $this->company_id,
            'department_id' => $this->department_id,
            'subject'       => $this->subject,
            'description'   => $this->description,
            'is_anonymous'  => (bool) $this->is_anonymous,
            'status'        => $this->status,
            'response'      => $this->response, // سيكون null حتى يقوم المدير بالرد
            
            // تطبيق القاعدة 4: لف التواريخ بـ Carbon::parse لتجنب أخطاء الـ String
            'created_at'    => Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at'    => Carbon::parse($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}