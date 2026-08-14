<?php

namespace App\Services\Owner;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BranchComparisonReportService
{
    public function getData(int $companyId): array
    {
        $branches = DB::table('branches')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        $period = DB::table('payroll_periods')
            ->where('company_id', $companyId)
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->whereNull('deleted_at')
            ->first();

        $rows = $branches->map(function ($branch) use ($period) {
            $employeesCount = DB::table('employee_details as ed')
                ->join('departments as d', 'd.id', '=', 'ed.department_id')
                ->where('d.branch_id', $branch->id)
                ->where('ed.employment_status', 'active')
                ->whereNull('ed.deleted_at')
                ->count();

            $presentToday = DB::table('attendance_logs')
                ->where('branch_id', $branch->id)
                ->whereDate('check_in', Carbon::today())
                ->whereIn('status', ['present', 'late'])
                ->whereNull('deleted_at')
                ->distinct('employee_user_id')
                ->count('employee_user_id');

            $attendanceRate = $employeesCount > 0 ? round(($presentToday / $employeesCount) * 100, 1) : 0;

            $pendingExceptions = DB::table('exception_requests as er')
                ->join('employee_details as ed', 'ed.id', '=', 'er.employee_id')
                ->join('departments as d', 'd.id', '=', 'ed.department_id')
                ->where('d.branch_id', $branch->id)
                ->where('er.status', 'pending')
                ->whereNull('er.deleted_at')
                ->count();

            $branchPayroll = 0;
            if ($period) {
                $branchPayroll = (float) DB::table('payroll_records as pr')
                    ->join('users as u', 'u.id', '=', 'pr.employee_user_id')
                    ->join('employee_details as ed', 'ed.user_id', '=', 'u.id')
                    ->join('departments as d', 'd.id', '=', 'ed.department_id')
                    ->where('d.branch_id', $branch->id)
                    ->where('pr.period_id', $period->id)
                    ->whereNull('pr.deleted_at')
                    ->sum('pr.net_salary');
            }

            return [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'employees_count' => $employeesCount,
                'attendance_rate' => $attendanceRate,
                'pending_exceptions' => $pendingExceptions,
                'monthly_payroll' => $branchPayroll,
            ];
        });

        return [
            'chart' => [
                'employees' => $rows->pluck('employees_count', 'branch_name')->toArray(),
                'attendance_rate' => $rows->pluck('attendance_rate', 'branch_name')->toArray(),
            ],
            'records' => $rows->all(),
        ];
    }
}