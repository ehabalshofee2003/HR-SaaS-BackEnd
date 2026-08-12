<?php

namespace App\Repositories\Reports;

use Illuminate\Support\Facades\DB;

class ReportRepository
{
    private function baseEmployeeScope(int $branchId, array $filters)
    {
        $query = DB::table('employee_details')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->whereNull('employee_details.deleted_at');

        if (!empty($filters['department_id'])) {
            $query->where('employee_details.department_id', $filters['department_id']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('employee_details.user_id', $filters['employee_id']);
        }

        return $query;
    }

    // ================= تقرير الحضور =================

    public function attendanceTable(int $branchId, array $filters): array
    {
        $employeeIds = $this->baseEmployeeScope($branchId, $filters)->pluck('employee_details.user_id');

        return DB::table('attendance_logs')
            ->join('user_profiles', 'attendance_logs.employee_user_id', '=', 'user_profiles.user_id')
            ->whereIn('attendance_logs.employee_user_id', $employeeIds)
            ->whereBetween('attendance_logs.check_in', [$filters['from'] . ' 00:00:00', $filters['to'] . ' 23:59:59'])
            ->whereNull('attendance_logs.deleted_at')
            ->select(
                'user_profiles.full_name as employee_name',
                DB::raw("SUM(CASE WHEN attendance_logs.status = 'present' THEN 1 ELSE 0 END) as present_days"),
                DB::raw("SUM(CASE WHEN attendance_logs.status = 'absent' THEN 1 ELSE 0 END) as absent_days"),
                DB::raw("SUM(CASE WHEN attendance_logs.status = 'late' THEN 1 ELSE 0 END) as late_days")
            )
            ->groupBy('attendance_logs.employee_user_id', 'user_profiles.full_name')
            ->get()
            ->toArray();
    }

    public function attendanceChart(int $branchId, array $filters): array
    {
        $employeeIds = $this->baseEmployeeScope($branchId, $filters)->pluck('employee_details.user_id');

        return DB::table('attendance_logs')
            ->whereIn('employee_user_id', $employeeIds)
            ->whereBetween('check_in', [$filters['from'] . ' 00:00:00', $filters['to'] . ' 23:59:59'])
            ->whereNull('deleted_at')
            ->select(
                DB::raw('DATE(check_in) as date'),
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late")
            )
            ->groupBy(DB::raw('DATE(check_in)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    // ================= تقرير المهام =================

    public function tasksTable(int $branchId, array $filters): array
    {
        $employeeIds = $this->baseEmployeeScope($branchId, $filters)->pluck('employee_details.user_id');

        return DB::table('tasks')
            ->join('user_profiles', 'tasks.employee_user_id', '=', 'user_profiles.user_id')
            ->whereIn('tasks.employee_user_id', $employeeIds)
            ->whereBetween('tasks.created_at', [$filters['from'] . ' 00:00:00', $filters['to'] . ' 23:59:59'])
            ->whereNull('tasks.deleted_at')
            ->select(
                'user_profiles.full_name as employee_name',
                DB::raw("SUM(CASE WHEN tasks.status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN tasks.status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN tasks.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress"),
                DB::raw("SUM(CASE WHEN tasks.due_date < NOW() AND tasks.status NOT IN ('completed','cancelled') THEN 1 ELSE 0 END) as overdue")
            )
            ->groupBy('tasks.employee_user_id', 'user_profiles.full_name')
            ->get()
            ->toArray();
    }

    public function tasksChart(int $branchId, array $filters): array
    {
        $employeeIds = $this->baseEmployeeScope($branchId, $filters)->pluck('employee_details.user_id');

        return DB::table('tasks')
            ->whereIn('employee_user_id', $employeeIds)
            ->whereBetween('created_at', [$filters['from'] . ' 00:00:00', $filters['to'] . ' 23:59:59'])
            ->whereNull('deleted_at')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    // ================= التقرير المالي =================

    public function financialTable(int $branchId, array $filters): array
    {
        $employeeIds = $this->baseEmployeeScope($branchId, $filters)->pluck('employee_details.user_id');

        return DB::table('payroll_records')
            ->join('payroll_periods', 'payroll_records.period_id', '=', 'payroll_periods.id')
            ->join('user_profiles', 'payroll_records.employee_user_id', '=', 'user_profiles.user_id')
            ->whereIn('payroll_records.employee_user_id', $employeeIds)
            ->whereBetween('payroll_periods.start_date', [$filters['from'], $filters['to']])
            ->select(
                'user_profiles.full_name as employee_name',
                'payroll_periods.month',
                'payroll_periods.year',
                'payroll_records.gross_salary',
                'payroll_records.total_deductions',
                'payroll_records.total_bonuses',
                'payroll_records.net_salary',
                'payroll_records.status'
            )
            ->orderBy('payroll_periods.year')
            ->orderBy('payroll_periods.month')
            ->get()
            ->toArray();
    }

    public function financialChart(int $branchId, array $filters): array
    {
        $employeeIds = $this->baseEmployeeScope($branchId, $filters)->pluck('employee_details.user_id');

        return DB::table('payroll_records')
            ->join('payroll_periods', 'payroll_records.period_id', '=', 'payroll_periods.id')
            ->whereIn('payroll_records.employee_user_id', $employeeIds)
            ->whereBetween('payroll_periods.start_date', [$filters['from'], $filters['to']])
            ->select(
                'payroll_periods.month',
                'payroll_periods.year',
                DB::raw('SUM(payroll_records.net_salary) as total_net'),
                DB::raw('SUM(payroll_records.total_deductions) as total_deductions'),
                DB::raw('SUM(payroll_records.total_bonuses) as total_bonuses')
            )
            ->groupBy('payroll_periods.year', 'payroll_periods.month')
            ->orderBy('payroll_periods.year')
            ->orderBy('payroll_periods.month')
            ->get()
            ->toArray();
    }

    // ================= تقرير الأداء =================

    public function performanceTable(int $branchId, array $filters): array
    {
        $employeeIds = $this->baseEmployeeScope($branchId, $filters)->pluck('employee_details.user_id');

        return DB::table('performance_evaluations')
            ->join('user_profiles', 'performance_evaluations.employee_user_id', '=', 'user_profiles.user_id')
            ->whereIn('performance_evaluations.employee_user_id', $employeeIds)
            ->whereBetween('performance_evaluations.created_at', [$filters['from'] . ' 00:00:00', $filters['to'] . ' 23:59:59'])
            ->whereNull('performance_evaluations.deleted_at')
            ->select(
                'user_profiles.full_name as employee_name',
                DB::raw('AVG(performance_evaluations.overall_score) as average_score'),
                DB::raw('COUNT(*) as evaluations_count')
            )
            ->groupBy('performance_evaluations.employee_user_id', 'user_profiles.full_name')
            ->get()
            ->toArray();
    }

    public function performanceChart(int $branchId, array $filters): array
    {
        $employeeIds = $this->baseEmployeeScope($branchId, $filters)->pluck('employee_details.user_id');

        return DB::table('performance_evaluations')
            ->whereIn('employee_user_id', $employeeIds)
            ->whereBetween('created_at', [$filters['from'] . ' 00:00:00', $filters['to'] . ' 23:59:59'])
            ->whereNull('deleted_at')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('AVG(overall_score) as average_score')
            )
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->orderBy('month')
            ->get()
            ->toArray();
    }
}