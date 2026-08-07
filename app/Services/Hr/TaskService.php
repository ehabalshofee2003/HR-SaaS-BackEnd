<?php

namespace App\Services\Hr;

use App\Repositories\Hr\TaskRepository;
use App\Models\Identity\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Models\Hr\Task;
use Exception;

class TaskService
{
    public function __construct(
        private TaskRepository $taskRepository
    ) {}

    // ================= دوال Branch Manager (جديدة) =================

    public function list(User $manager, array $filters)
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->taskRepository->paginateForBranch($branchId, $filters);
    }

    public function getDetails(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $task = $this->taskRepository->findForBranch($id, $branchId);

        if (!$task) {
            throw new Exception('المهمة غير موجودة.', 404);
        }

        return $task;
    }

    public function createGeneral(User $manager, array $data): object
    {
        $branchId = $manager->getCurrentBranchId();
        $companyId = $manager->getCurrentCompanyId();

        $employeeDetail = $this->taskRepository->employeeInBranch($data['employee_user_id'], $branchId);

        if (!$employeeDetail) {
            throw new Exception('الموظف المحدد لا ينتمي لهذا الفرع.');
        }

        if (empty($employeeDetail->supervisor_id)) {
            throw new Exception('لا يمكن إسناد مهمة لهذا الموظف لأن قسمه بلا مشرف حالياً. يرجى تعيين مشرف للقسم أولاً.');
        }

        $id = $this->taskRepository->createGeneral([
            'company_id' => $companyId,
            'employee_user_id' => $data['employee_user_id'],
            'supervisor_user_id' => $employeeDetail->supervisor_id,
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? 'medium',
            'due_date' => $data['due_date'],
        ]);

        // TODO: إشعار الموظف والمشرف التابع له

        return $this->taskRepository->findForBranch($id, $branchId);
    }

    public function updateGeneral(int $id, array $data, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $task = $this->taskRepository->findForBranch($id, $branchId);

        if (!$task) {
            throw new Exception('المهمة غير موجودة.', 404);
        }

        if ($task->status === 'completed') {
            throw new Exception('لا يمكن تعديل مهمة مكتملة.');
        }

        if (!empty($data['employee_user_id']) && $data['employee_user_id'] != $task->employee_user_id) {
            $employeeDetail = $this->taskRepository->employeeInBranch($data['employee_user_id'], $branchId);
            if (!$employeeDetail) {
                throw new Exception('الموظف المحدد لا ينتمي لهذا الفرع.');
            }
            if (empty($employeeDetail->supervisor_id)) {
                throw new Exception('لا يمكن إسناد مهمة لهذا الموظف لأن قسمه بلا مشرف حالياً. يرجى تعيين مشرف للقسم أولاً.');
            }
            $data['supervisor_user_id'] = $employeeDetail->supervisor_id;
        }
        $this->taskRepository->updateGeneral($id, $data);

        // TODO: إشعار المشرف إذا كانت المهمة أنشأها مشرف أصلاً وقام مدير الفرع بتعديلها

        return $this->taskRepository->findForBranch($id, $branchId);
    }

    public function deleteGeneral(int $id, User $manager): void
    {
        $branchId = $manager->getCurrentBranchId();
        $task = $this->taskRepository->findForBranch($id, $branchId);

        if (!$task) {
            throw new Exception('المهمة غير موجودة.', 404);
        }

        if ($task->status === 'completed') {
            throw new Exception('لا يمكن حذف مهمة مكتملة.');
        }

        $this->taskRepository->softDeleteGeneral($id);
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

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