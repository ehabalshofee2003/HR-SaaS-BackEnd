<?php

namespace App\Repositories\Interfaces\Owner;

interface DashboardRepositoryInterface
{
    public function getCompany(int $companyId): ?object;
    public function countBranches(int $companyId): int;
    public function countActiveEmployees(int $companyId): int;
    public function countTodayPresent(int $companyId): int;
    public function countPendingExceptions(int $companyId): int;
    public function currentMonthlyPayroll(int $companyId): float;
    public function branchComparison(int $companyId): array;
    public function weeklyAttendance(int $companyId): array;
    public function latestActivity(int $companyId, int $limit = 5): array;
}