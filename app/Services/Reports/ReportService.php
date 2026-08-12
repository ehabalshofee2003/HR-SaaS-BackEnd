<?php

namespace App\Services\Reports;

use App\Repositories\Reports\ReportRepository;
use App\Models\Identity\User;

class ReportService
{
    public function __construct(
        protected ReportRepository $reportRepository
    ) {}

    public function attendance(User $manager, array $filters): array
    {
        $branchId = $manager->getCurrentBranchId();
        return [
            'chart_data' => $this->reportRepository->attendanceChart($branchId, $filters),
            'table_data' => $this->reportRepository->attendanceTable($branchId, $filters),
        ];
    }

    public function tasks(User $manager, array $filters): array
    {
        $branchId = $manager->getCurrentBranchId();
        return [
            'chart_data' => $this->reportRepository->tasksChart($branchId, $filters),
            'table_data' => $this->reportRepository->tasksTable($branchId, $filters),
        ];
    }

    public function financial(User $manager, array $filters): array
    {
        $branchId = $manager->getCurrentBranchId();
        return [
            'chart_data' => $this->reportRepository->financialChart($branchId, $filters),
            'table_data' => $this->reportRepository->financialTable($branchId, $filters),
        ];
    }

    public function performance(User $manager, array $filters): array
    {
        $branchId = $manager->getCurrentBranchId();
        return [
            'chart_data' => $this->reportRepository->performanceChart($branchId, $filters),
            'table_data' => $this->reportRepository->performanceTable($branchId, $filters),
        ];
    }
}