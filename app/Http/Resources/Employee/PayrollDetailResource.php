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
        $allDetails = $this->whenLoaded('details') ? $this->details : collect();

        // فصل البنود: خصومات لحالها، إضافات لحالها (باستثناء الراتب الأساسي نفسه)
        $deductions = $allDetails->where('component_type', 'deduction')
            ->map(fn($d) => ['name' => $d->name, 'amount' => (float) $d->amount])
            ->values();

        $bonuses = $allDetails->whereIn('component_type', ['bonus', 'allowance'])
            ->map(fn($d) => ['name' => $d->name, 'amount' => (float) $d->amount])
            ->values();

        return [
            'id' => $this->id,
            'month' => $period ? Carbon::create($period->year, $period->month, 1)->format('F') : null,
            'year' => $period?->year,
            'status' => $this->status === 'paid' ? 'paid' : 'progressing',

            'basic_salary' => (float) $this->gross_salary,

            // إحصائيات الحضور للشهر
            'work_days' => $this->work_days ?? null,
            'absent_days' => $this->absent_days ?? null,
            'late_hours' => $this->late_hours ?? null,

            // تفصيل الخصومات
            'deductions' => $deductions,
            'total_deductions' => (float) $this->total_deductions,

            // تفصيل الإضافات
            'bonuses' => $bonuses,
            'total_bonuses' => (float) $this->total_bonuses,

            // الصافي النهائي = الأساسي - إجمالي الخصومات + إجمالي الإضافات
            'net_salary' => (float) $this->net_salary,

            'paid_at' => $this->paid_at ? Carbon::parse($this->paid_at)->format('Y-m-d H:i:s') : null,
            'approved_at' => $this->approved_at ? Carbon::parse($this->approved_at)->format('Y-m-d H:i:s') : null,
        ];
    }
}