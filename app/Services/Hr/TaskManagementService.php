<?php

namespace App\Services\Supervisor;

use App\Repositories\Interfaces\Supervisor\TaskManagementRepositoryInterface;
use App\Repositories\Interfaces\Supervisor\EmployeeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TaskManagementService
{
    public function __construct(
        private TaskManagementRepositoryInterface $repository,
        private EmployeeRepositoryInterface $employeeRepository,
    ) {}

    public function list(int $supervisorId, array $filters): array
    {
        return array_map(fn($t) => $this->formatListItem($t), $this->repository->list($supervisorId, $filters));
    }

    public function get(int $id, int $supervisorId): array
    {
        $task = $this->repository->findForSupervisor($id, $supervisorId);
        if (!$task) throw ValidationException::withMessages(['task' => ['Task not found.']]);
        return $this->formatDetails($task);
    }

    public function create(int $supervisorId, array $data): array
    {
        $employee = $this->employeeRepository->find($data['employee_id'], $supervisorId);
        if (!$employee) throw ValidationException::withMessages(['employee_id' => ['Employee not found or not assigned to you.']]);

        $companyId = \App\Models\Identity\User::find($supervisorId)?->getCurrentCompanyId();
        if (!$companyId) throw ValidationException::withMessages(['company' => ['Could not determine company.']]);

        $dueDateTime = Carbon::parse($data['due_date'] . ' ' . $data['due_time']);

        $taskId = $this->repository->create([
            'company_id' => $companyId, 'employee_user_id' => $data['employee_id'],
            'supervisor_user_id' => $supervisorId, 'title' => $data['title'],
            'description' => $data['description'] ?? null, 'type' => 'ad_hoc',
            'priority' => $data['priority'], 'due_date' => $dueDateTime, 'status' => 'pending',
        ]);

        return $this->get($taskId, $supervisorId);
    }

    public function update(int $id, int $supervisorId, array $data): array
    {
        $task = $this->repository->findForSupervisor($id, $supervisorId);
        if (!$task) throw ValidationException::withMessages(['task' => ['Task not found.']]);

        $updateData = [];

        if (!empty($data['employee_id'])) {
            $employee = $this->employeeRepository->find($data['employee_id'], $supervisorId);
            if (!$employee) throw ValidationException::withMessages(['employee_id' => ['Employee not found or not assigned to you.']]);
            $updateData['employee_user_id'] = $data['employee_id'];
        }

        if (!empty($data['title'])) $updateData['title'] = $data['title'];
        if (array_key_exists('description', $data)) $updateData['description'] = $data['description'];
        if (!empty($data['priority'])) $updateData['priority'] = $data['priority'];

        if (!empty($data['status'])) {
            $updateData['status'] = $data['status'];
            if ($data['status'] === 'completed') $updateData['completed_at'] = now();
        }

        if (!empty($data['due_date']) || !empty($data['due_time'])) {
            $date = $data['due_date'] ?? Carbon::parse($task->due_date)->toDateString();
            $time = $data['due_time'] ?? Carbon::parse($task->due_date)->format('H:i');
            $updateData['due_date'] = Carbon::parse("{$date} {$time}");
        }

        $this->repository->update($id, $updateData);
        return $this->get($id, $supervisorId);
    }

    public function delete(int $id, int $supervisorId): array
    {
        $task = $this->repository->findForSupervisor($id, $supervisorId);
        if (!$task) throw ValidationException::withMessages(['task' => ['Task not found.']]);
        $this->repository->delete($id);
        return ['success' => true, 'message' => 'Task deleted successfully.'];
    }

    private function formatListItem(object $t): array
    {
        return [
            'id' => $t->id, 'employee_name' => $t->employee_name, 'title' => $t->title,
            'due_date' => Carbon::parse($t->due_date)->toIso8601String(),
            'priority' => $t->priority, 'status' => $t->status,
        ];
    }

    private function formatDetails(object $t): array
    {
        return [
            'id' => $t->id, 'title' => $t->title, 'description' => $t->description,
            'employee_id' => $t->employee_user_id, 'employee_name' => $t->employee_name,
            'due_date' => Carbon::parse($t->due_date)->toDateString(),
            'due_time' => Carbon::parse($t->due_date)->format('H:i'),
            'priority' => $t->priority, 'status' => $t->status,
            'completed_at' => $t->completed_at ? Carbon::parse($t->completed_at)->toIso8601String() : null,
            'created_at' => Carbon::parse($t->created_at)->toIso8601String(),
        ];
    }
}