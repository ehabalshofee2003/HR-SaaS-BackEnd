<?php

namespace App\Services\Hr;

use App\Repositories\Hr\TaskRepository;
use App\Models\Identity\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Hr\Task;

class TaskService
{
    public function __construct(
        private TaskRepository $taskRepository
    ) {}

    /**
     * جلب مهام الموظف الحالي
     */
    public function getEmployeeTasks(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->taskRepository->getTasksByEmployee($user->id, $filters);
    }
    public function getHomeTasks(int $employeeUserId): Collection
    {
        return $this->taskRepository->getHomeTasks($employeeUserId);
    }
    public function getTaskDetail(int $taskId, int $employeeUserId): ?Task
    {
        return $this->taskRepository->findTaskByIdForEmployee($taskId, $employeeUserId);
    }
    public function startTask(int $taskId, int $employeeUserId): array
    {
        $task = $this->taskRepository->findTaskByIdForEmployee($taskId, $employeeUserId);

        if (!$task) {
            return ['success' => false, 'message' => 'Task not found', 'code' => 404];
        }

        if ($task->status !== 'pending') {
            return ['success' => false, 'message' => 'Only pending tasks can be started', 'code' => 422];
        }

        $task->update(['status' => 'in_progress']);

        return ['success' => true, 'task' => $task];
    }

    public function completeTask(int $taskId, int $employeeUserId): array
    {
        $task = $this->taskRepository->findTaskByIdForEmployee($taskId, $employeeUserId);

        if (!$task) {
            return ['success' => false, 'message' => 'Task not found', 'code' => 404];
        }

        if ($task->status !== 'in_progress') {
            return ['success' => false, 'message' => 'Only in-progress tasks can be completed', 'code' => 422];
        }

        $task->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return ['success' => true, 'task' => $task];
    }
}