<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PayrollDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $period = $this->whenLoaded('period') ? $this->period : null;

        return [
            'id' => $this->id,

            // اسم الشهر ككلمة إنجليزية، وليس رقماً
            'month' => $period ? Carbon::create($period->year, $period->month, 1)->format('F') : null,
            'year' => $period?->year,

            // حالة القبض الفعلية لهذا الموظف تحديداً: paid = تم الدفع، progressing = لم يُدفع بعد
            'status' => $this->status === 'paid' ? 'paid' : 'progressing',

            // الراتب الأساسي وحده، بدون أي خصومات أو إضافات
            'basic_salary' => (float) $this->gross_salary,

            // الراتب الصافي الكامل بعد كل الحسابات
            'net_salary' => (float) $this->net_salary,

            'paid_at' => $this->paid_at ? Carbon::parse($this->paid_at)->format('Y-m-d H:i:s') : null,
            'approved_at' => $this->approved_at ? Carbon::parse($this->approved_at)->format('Y-m-d H:i:s') : null,

            // تفاصيل كل بند (تظهر فقط إذا كانت العلاقة محمَّلة مسبقاً بالكونترولر)
            'details' => PayrollComponentResource::collection($this->whenLoaded('details')),
        ];
    }
}