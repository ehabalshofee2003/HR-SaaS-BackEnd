<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attendance = $this->resource['attendance_today'];
        $payslip = $this->resource['latest_payslip'];

        return [
            'attendance_today' => $attendance ? [
                'status'    => $attendance->status,
                'check_in'  => Carbon::parse($attendance->check_in)->format('Y-m-d H:i:s'),
                'check_out' => $attendance->check_out
                    ? Carbon::parse($attendance->check_out)->format('Y-m-d H:i:s')
                    : null,
            ] : null,

            'tasks_summary' => [
                'pending_count' => $this->resource['pending_tasks_count'],
                'overdue_count' => $this->resource['overdue_tasks_count'],
            ],

            'annual_leave_balance' => $this->resource['annual_leave_balance'],

            'latest_payslip' => $payslip ? [
                'month'      => $payslip->period->month,
                'year'       => $payslip->period->year,
                'net_salary' => $payslip->net_salary,
                'status'     => $payslip->status,
            ] : null,

            'recent_announcements' => $this->resource['recent_announcements']->map(fn ($a) => [
                'id'         => $a->id,
                'title'      => $a->title,
                'created_at' => Carbon::parse($a->created_at)->format('Y-m-d H:i:s'),
            ]),

            'home_tasks' => HomeTaskResource::collection($this->resource['home_tasks']),
        ];
    }
}