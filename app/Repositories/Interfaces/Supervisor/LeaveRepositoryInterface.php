<?php

namespace App\Repositories\Interfaces\Supervisor;

interface LeaveRepositoryInterface
{
    public function balances(int $employeeUserId): array;
    public function history(int $employeeUserId): array;
}