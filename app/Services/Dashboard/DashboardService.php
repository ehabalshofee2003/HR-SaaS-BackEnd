<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\DashboardRepository;
use App\Models\Identity\User;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        protected DashboardRepository $dashboardRepository
    ) {}

    public function get(User $manager): array
    {
        $branchId = $manager->getCurrentBranchId();

        return [
            'cards' => [
                'total_employees' => $this->dashboardRepository->totalEmployees($branchId),
                'present_today' => $this->dashboardRepository->presentToday($branchId),
                'absent_today' => $this->dashboardRepository->absentToday($branchId),
                'attendance_rate_today' => $this->dashboardRepository->attendanceRateToday($branchId),
                'pending_tasks' => $this->dashboardRepository->pendingTasksCount($branchId),
                'pending_leave_requests' => $this->dashboardRepository->pendingLeavesCount($branchId),
                'pending_exception_requests' => $this->dashboardRepository->pendingExceptionRequestsCount($branchId),
                'pending_complaints' => $this->dashboardRepository->pendingComplaintsCount($branchId),
                'pending_resignations' => $this->dashboardRepository->pendingResignationsCount($branchId),
                'monthly_payroll_total' => $this->dashboardRepository->monthlyPayrollTotal($branchId),
            ],
            'charts' => [
                'weekly_attendance' => $this->dashboardRepository->weeklyAttendanceChart($branchId),
                'employees_by_department' => $this->dashboardRepository->employeesByDepartmentChart($branchId),
                'monthly_payroll' => array_map(function ($row) {
                    return [
                        'month' => Carbon::create($row->year, $row->month, 1)->format('F'),
                        'year' => $row->year,
                        'total_net' => (float) $row->total_net,
                    ];
                }, $this->dashboardRepository->monthlyPayrollChart($branchId)),
            ],
            'lists' => [
                'last_overdue_tasks' => $this->dashboardRepository->lastOverdueTasks($branchId),
                'last_leave_requests' => $this->dashboardRepository->lastLeaveRequests($branchId),
                'last_complaints' => $this->dashboardRepository->lastComplaints($branchId),
            ],
        ];
    }
}