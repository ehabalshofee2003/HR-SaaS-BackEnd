<?php

namespace App\Services\Owner;

use App\Repositories\Interfaces\Owner\DashboardRepositoryInterface;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private DashboardRepositoryInterface $repository,
    ) {}

    public function getDashboard(int $companyId): array
    {
        $company = $this->repository->getCompany($companyId);

        $totalEmployees = $this->repository->countActiveEmployees($companyId);
        $todayPresent = $this->repository->countTodayPresent($companyId);
        $attendanceRate = $totalEmployees > 0
            ? round(($todayPresent / $totalEmployees) * 100, 1)
            : 0.0;

        return [
            'company' => [
                'name' => $company->name ?? null,
                'logo' => $company->logo ?? null,
                'status' => $company->status ?? null,
            ],
            'stats' => [
                'total_branches' => $this->repository->countBranches($companyId),
                'total_employees' => $totalEmployees,
                'attendance_rate' => $attendanceRate,
                'pending_requests' => $this->repository->countPendingExceptions($companyId),
                'monthly_payroll' => $this->repository->currentMonthlyPayroll($companyId),
            ],
            'branch_comparison' => $this->repository->branchComparison($companyId),
            'weekly_attendance_rate' => $this->repository->weeklyAttendance($companyId),
            'latest_activity' => array_map(fn($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'type' => $n->type,
                'created_at' => Carbon::parse($n->created_at)->toIso8601String(),
            ], $this->repository->latestActivity($companyId)),
        ];
    }
}