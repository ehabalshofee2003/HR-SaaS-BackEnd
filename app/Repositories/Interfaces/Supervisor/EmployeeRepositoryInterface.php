<?php

namespace App\Repositories\Interfaces\Supervisor;

interface EmployeeRepositoryInterface
{
    public function list(int $supervisorId): array;
    public function find(int $id, int $supervisorId): ?object;
    public function updateProfile(int $userId, array $employeeData): void;
    public function todayAttendance(int $employeeUserId): ?object;
    public function hasApprovedLeaveToday(int $employeeUserId): bool;
}