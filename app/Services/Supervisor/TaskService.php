<?php

namespace App\Services\Supervisor;

use App\Repositories\Interfaces\Supervisor\TaskRepositoryInterface;
use App\Repositories\Interfaces\Supervisor\EmployeeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository,
        private EmployeeRepositoryInterface $employeeRepository,
    ) {}

    public function listForEmployee(int $employeeId, int $supervisorId): array
    {
        $employee = $this->employeeRepository->find($employeeId, $supervisorId);

        if (!$employee) {
            throw ValidationException::withMessages(['employee' => ['Employee not found.']]);
        }

        $tasks = $this->taskRepository->listForEmployee($employeeId, $supervisorId);

        return array_map(fn($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'due_date' => Carbon::parse($t->due_date)->toIso8601String(),
            'priority' => $t->priority,
            'status' => $t->status,
        ], $tasks);
    }
}