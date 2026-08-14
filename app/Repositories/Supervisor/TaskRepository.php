<?php

namespace App\Repositories\Supervisor;

use App\Repositories\Interfaces\Supervisor\TaskRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TaskRepository implements TaskRepositoryInterface
{
    public function listForEmployee(int $employeeUserId, int $supervisorId): array
    {
        return DB::table('tasks')
            ->where('employee_user_id', $employeeUserId)
            ->where('supervisor_user_id', $supervisorId)
            ->whereNull('deleted_at')
            ->orderByDesc('due_date')
            ->get(['id', 'title', 'due_date', 'priority', 'status'])
            ->all();
    }
}