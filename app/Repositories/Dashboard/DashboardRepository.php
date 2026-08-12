<?php

namespace App\Repositories\Dashboard;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardRepository
{
    // ================= الـ 10 Cards =================

    public function totalEmployees(int $branchId): int
    {
        return DB::table('employee_details')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->where('employee_details.employment_status', 'active')
            ->whereNull('employee_details.deleted_at')
            ->count();
    }

    public function presentToday(int $branchId): int
    {
        return DB::table('attendance_logs')
            ->where('branch_id', $branchId)
            ->whereDate('check_in', Carbon::today())
            ->whereIn('status', ['present', 'late'])
            ->whereNull('deleted_at')
            ->count();
    }

    public function absentToday(int $branchId): int
    {
        return DB::table('attendance_logs')
            ->where('branch_id', $branchId)
            ->whereDate('check_in', Carbon::today())
            ->where('status', 'absent')
            ->whereNull('deleted_at')
            ->count();
    }

    public function pendingTasksCount(int $branchId): int
    {
        return DB::table('tasks')
            ->join('employee_details', 'tasks.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->whereIn('tasks.status', ['pending', 'in_progress'])
            ->whereNull('tasks.deleted_at')
            ->count();
    }

    public function pendingLeavesCount(int $branchId): int
    {
        return DB::table('leave_requests')
            ->join('employee_details', 'leave_requests.employee_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->where('leave_requests.status', 'pending_manager')
            ->whereNull('leave_requests.deleted_at')
            ->count();
    }

    public function pendingExceptionRequestsCount(int $branchId): int
    {
        return DB::table('exception_requests')
            ->join('employee_details', 'exception_requests.employee_id', '=', 'employee_details.id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->where('exception_requests.status', 'supervisor_reviewed')
            ->whereNull('exception_requests.deleted_at')
            ->count();
    }

    public function pendingComplaintsCount(int $branchId): int
    {
        return DB::table('complaints')
            ->leftJoin('departments', 'complaints.department_id', '=', 'departments.id')
            ->leftJoin('users as against', 'complaints.against_user_id', '=', 'against.id')
            ->where(function ($q) use ($branchId) {
                $q->where('departments.branch_id', $branchId)
                  ->orWhere('against.branch_id', $branchId);
            })
            ->whereIn('complaints.status', ['open', 'in_review'])
            ->whereNull('complaints.deleted_at')
            ->count();
    }

    public function pendingResignationsCount(int $branchId): int
    {
        return DB::table('resignations')
            ->join('employee_details', 'resignations.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->where('resignations.status', 'pending')
            ->whereNull('resignations.deleted_at')
            ->count();
    }

    public function monthlyPayrollTotal(int $branchId): float
    {
        $now = Carbon::now();

        return (float) DB::table('payroll_records')
            ->join('payroll_periods', 'payroll_records.period_id', '=', 'payroll_periods.id')
            ->join('employee_details', 'payroll_records.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->where('payroll_periods.month', $now->month)
            ->where('payroll_periods.year', $now->year)
            ->sum('payroll_records.net_salary');
    }

    public function attendanceRateToday(int $branchId): float
    {
        $total = $this->totalEmployees($branchId);
        if ($total === 0) return 0;

        $present = $this->presentToday($branchId);
        return round(($present / $total) * 100, 2);
    }

    // ================= الـ 3 Charts =================

    public function weeklyAttendanceChart(int $branchId): array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();

        return DB::table('attendance_logs')
            ->where('branch_id', $branchId)
            ->where('check_in', '>=', $startDate)
            ->whereNull('deleted_at')
            ->select(
                DB::raw('DATE(check_in) as date'),
                DB::raw("SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent")
            )
            ->groupBy(DB::raw('DATE(check_in)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function employeesByDepartmentChart(int $branchId): array
    {
        return DB::table('employee_details')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->where('employee_details.employment_status', 'active')
            ->whereNull('employee_details.deleted_at')
            ->select('departments.name as department_name', DB::raw('COUNT(*) as employee_count'))
            ->groupBy('departments.id', 'departments.name')
            ->get()
            ->toArray();
    }

    public function monthlyPayrollChart(int $branchId): array
    {
        $startDate = Carbon::now()->subMonths(5)->startOfMonth();

        return DB::table('payroll_records')
            ->join('payroll_periods', 'payroll_records.period_id', '=', 'payroll_periods.id')
            ->join('employee_details', 'payroll_records.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->where('payroll_periods.start_date', '>=', $startDate->toDateString())
            ->select(
                'payroll_periods.month',
                'payroll_periods.year',
                DB::raw('SUM(payroll_records.net_salary) as total_net')
            )
            ->groupBy('payroll_periods.year', 'payroll_periods.month')
            ->orderBy('payroll_periods.year')
            ->orderBy('payroll_periods.month')
            ->get()
            ->toArray();
    }

    // ================= آخر 5 قوائم =================

    public function lastOverdueTasks(int $branchId): array
    {
        return DB::table('tasks')
            ->join('employee_details', 'tasks.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->join('user_profiles', 'tasks.employee_user_id', '=', 'user_profiles.user_id')
            ->where('departments.branch_id', $branchId)
            ->where('tasks.due_date', '<', Carbon::now())
            ->whereNotIn('tasks.status', ['completed', 'cancelled'])
            ->whereNull('tasks.deleted_at')
            ->select(
                'tasks.id', 'tasks.title', 'tasks.due_date',
                'user_profiles.full_name as employee_name',
                DB::raw('DATEDIFF(NOW(), tasks.due_date) as days_overdue')
            )
            ->orderByDesc('days_overdue')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function lastLeaveRequests(int $branchId): array
    {
        return DB::table('leave_requests')
            ->join('employee_details', 'leave_requests.employee_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->join('user_profiles', 'leave_requests.employee_id', '=', 'user_profiles.user_id')
            ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->where('departments.branch_id', $branchId)
            ->whereNull('leave_requests.deleted_at')
            ->select(
                'leave_requests.id', 'leave_requests.start_date', 'leave_requests.end_date', 'leave_requests.status',
                'user_profiles.full_name as employee_name',
                'leave_types.name as leave_type_name'
            )
            ->orderByDesc('leave_requests.created_at')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function lastComplaints(int $branchId): array
    {
        return DB::table('complaints')
            ->leftJoin('departments', 'complaints.department_id', '=', 'departments.id')
            ->leftJoin('users as against', 'complaints.against_user_id', '=', 'against.id')
            ->leftJoin('user_profiles', 'complaints.user_id', '=', 'user_profiles.user_id')
            ->where(function ($q) use ($branchId) {
                $q->where('departments.branch_id', $branchId)
                  ->orWhere('against.branch_id', $branchId);
            })
            ->whereNull('complaints.deleted_at')
            ->select(
                'complaints.id', 'complaints.subject', 'complaints.status', 'complaints.is_anonymous',
                DB::raw("CASE WHEN complaints.is_anonymous = 1 THEN 'مجهول' ELSE user_profiles.full_name END as employee_name")
            )
            ->orderByDesc('complaints.created_at')
            ->limit(5)
            ->get()
            ->toArray();
    }
}