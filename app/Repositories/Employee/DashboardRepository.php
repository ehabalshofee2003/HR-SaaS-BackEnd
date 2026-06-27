<?php

namespace App\Repositories\Employee;

use App\Models\Hr\AttendanceLog;
use App\Models\Hr\Task;
use App\Models\Hr\LeaveBalance;
use App\Models\Payroll\PayrollRecord;
use App\Models\Support\Announcement;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardRepository
{
    public function getAttendanceToday(int $employeeUserId): ?AttendanceLog
    {
        return AttendanceLog::where('employee_user_id', $employeeUserId)
            ->whereDate('check_in', Carbon::today())
            ->first();
    }

    public function getPendingTasksCount(int $employeeUserId): int
    {
        return Task::where('employee_user_id', $employeeUserId)
            ->where('status', 'pending')
            ->count();
    }

    public function getOverdueTasksCount(int $employeeUserId): int
    {
        return Task::where('employee_user_id', $employeeUserId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('due_date', '<', now())
            ->count();
    }

    public function getAnnualLeaveBalance(int $employeeUserId): string
    {
        $balance = LeaveBalance::where('employee_user_id', $employeeUserId)
            ->where('year', Carbon::now()->year)
            ->whereHas('policy', function ($query) {
                $query->where('leave_type', 'annual');
            })
            ->value('remaining_days');

        return $balance ?? '0.00';
    }

    public function getLatestPayslip(int $employeeUserId): ?PayrollRecord
    {
        return PayrollRecord::where('employee_user_id', $employeeUserId)
            ->whereIn('status', ['approved', 'paid'])
            ->with('period')
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getRecentAnnouncements(
        int $companyId,
        int $branchId,
        int $departmentId,
        int $employeeUserId
    ): Collection {
        return Announcement::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('start_date', '<=', today())
            ->where('end_date', '>=', today())
            ->where(function ($query) use ($branchId, $departmentId, $employeeUserId) {
                $query->where('target_type', 'all')
                    ->orWhere(function ($q) use ($branchId) {
                        $q->where('target_type', 'branch')
                            ->where('target_id', $branchId);
                    })
                    ->orWhere(function ($q) use ($departmentId) {
                        $q->where('target_type', 'department')
                            ->where('target_id', $departmentId);
                    })
                    ->orWhere(function ($q) use ($employeeUserId) {
                        $q->where('target_type', 'employee')
                            ->where('target_id', $employeeUserId);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
    }
}