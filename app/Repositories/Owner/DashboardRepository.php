<?php

namespace App\Repositories\Owner;

use App\Repositories\Interfaces\Owner\DashboardRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function getCompany(int $companyId): ?object
    {
        return DB::table('companies')->where('id', $companyId)->first();
    }

    public function countBranches(int $companyId): int
    {
        return DB::table('branches')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->count();
    }

    public function countActiveEmployees(int $companyId): int
    {
        return DB::table('employee_details')
            ->join('departments', 'departments.id', '=', 'employee_details.department_id')
            ->join('branches', 'branches.id', '=', 'departments.branch_id')
            ->where('branches.company_id', $companyId)
            ->where('employee_details.employment_status', 'active')
            ->whereNull('employee_details.deleted_at')
            ->whereNull('branches.deleted_at')
            ->count();
    }

    public function countTodayPresent(int $companyId): int
    {
        return DB::table('attendance_logs')
            ->where('company_id', $companyId)
            ->whereDate('check_in', Carbon::today())
            ->whereIn('status', ['present', 'late'])
            ->whereNull('deleted_at')
            ->distinct('employee_user_id')
            ->count('employee_user_id');
    }

    public function countPendingExceptions(int $companyId): int
    {
        return DB::table('exception_requests')
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->count();
    }

    public function currentMonthlyPayroll(int $companyId): float
    {
        $period = DB::table('payroll_periods')
            ->where('company_id', $companyId)
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->whereNull('deleted_at')
            ->first();

        if (!$period) {
            return 0.0;
        }

        return (float) DB::table('payroll_records')
            ->where('period_id', $period->id)
            ->whereNull('deleted_at')
            ->sum('net_salary');
    }

    public function branchComparison(int $companyId): array
    {
        $branches = DB::table('branches')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->get(['id', 'name']);

        return $branches->map(function ($branch) {
            $employeesCount = DB::table('employee_details')
                ->join('departments', 'departments.id', '=', 'employee_details.department_id')
                ->where('departments.branch_id', $branch->id)
                ->where('employee_details.employment_status', 'active')
                ->whereNull('employee_details.deleted_at')
                ->count();

            $presentToday = DB::table('attendance_logs')
                ->where('branch_id', $branch->id)
                ->whereDate('check_in', Carbon::today())
                ->whereIn('status', ['present', 'late'])
                ->whereNull('deleted_at')
                ->distinct('employee_user_id')
                ->count('employee_user_id');

            $attendancePercentage = $employeesCount > 0
                ? round(($presentToday / $employeesCount) * 100, 1)
                : 0.0;

            return [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'employees_count' => $employeesCount,
                'attendance_percentage' => $attendancePercentage,
            ];
        })->all();
    }

    public function weeklyAttendance(int $companyId): array
    {
        $totalEmployees = $this->countActiveEmployees($companyId);
        $result = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);

            $present = DB::table('attendance_logs')
                ->where('company_id', $companyId)
                ->whereDate('check_in', $date)
                ->whereIn('status', ['present', 'late'])
                ->whereNull('deleted_at')
                ->distinct('employee_user_id')
                ->count('employee_user_id');

            $rate = $totalEmployees > 0 ? round(($present / $totalEmployees) * 100, 1) : 0.0;

            $result[] = [
                'date' => $date->toDateString(),
                'day' => $date->translatedFormat('D'),
                'rate' => $rate,
            ];
        }

        return $result;
    }

    public function latestActivity(int $companyId, int $limit = 5): array
    {
        return DB::table('notifications')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'title', 'body', 'type', 'created_at'])
            ->all();
    }
}