<?php

namespace App\Repositories\Interfaces\Supervisor;

interface TaskRepositoryInterface
{
    public function listForEmployee(int $employeeUserId, int $supervisorId): array;
}