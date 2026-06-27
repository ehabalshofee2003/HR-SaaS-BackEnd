<?php

namespace App\Services\Employee;

use App\Models\Identity\User;
use App\Repositories\Employee\DashboardRepository;
use App\Repositories\Hr\TaskRepository;

class DashboardService
{
    public function __construct(
        protected DashboardRepository $dashboardRepository,
        protected TaskRepository $taskRepository
    ) {}

    public function getDashboard(int $userId): array
    {
        $user = User::find($userId);

        $departmentId = $user?->employeeDetail?->department_id;
        $branchId = $user?->employeeDetail?->department?->branch_id;
        $companyId = $user?->getCurrentCompanyId();

        return [
            'attendance_today'      => $this->dashboardRepository->getAttendanceToday($userId),
            'pending_tasks_count'   => $this->dashboardRepository->getPendingTasksCount($userId),
            'overdue_tasks_count'   => $this->dashboardRepository->getOverdueTasksCount($userId),
            'annual_leave_balance'  => $this->dashboardRepository->getAnnualLeaveBalance($userId),
            'latest_payslip'        => $this->dashboardRepository->getLatestPayslip($userId),
            'recent_announcements'  => $companyId
                ? $this->dashboardRepository->getRecentAnnouncements($companyId, $branchId, $departmentId, $userId)
                : collect(),
            'home_tasks'            => $this->taskRepository->getHomeTasks($userId),
        ];
    }
}