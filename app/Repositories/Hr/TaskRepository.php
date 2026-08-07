<?php

namespace App\Repositories\Hr;

use App\Models\Hr\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaskRepository
{
    // ================= دوال Branch Manager (جديدة) =================

    public function paginateForBranch(int $branchId, array $filters, int $perPage = 15)
    {
        $query = DB::table('tasks')
            ->join('employee_details', 'tasks.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->join('user_profiles', 'tasks.employee_user_id', '=', 'user_profiles.user_id')
            ->where('departments.branch_id', $branchId)
            ->whereNull('tasks.deleted_at')
            ->select(
                'tasks.id',
                'tasks.title',
                'tasks.status',
                'tasks.priority',
                'tasks.due_date',
                'tasks.type',
                'user_profiles.full_name as employee_name',
                'employee_details.department_id',
                'departments.name as department_name'
            );

        if (!empty($filters['department_id'])) {
            $query->where('employee_details.department_id', $filters['department_id']);
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'overdue') {
                $query->where('tasks.due_date', '<', Carbon::now())
                      ->whereNotIn('tasks.status', ['completed', 'cancelled']);
            } else {
                $query->where('tasks.status', $filters['status']);
            }
        }

        return $query->orderByDesc('tasks.due_date')->paginate($perPage);
    }

    public function findForBranch(int $id, int $branchId): ?object
    {
        return DB::table('tasks')
            ->join('employee_details', 'tasks.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('tasks.id', $id)
            ->where('departments.branch_id', $branchId)
            ->whereNull('tasks.deleted_at')
            ->select('tasks.*')
            ->first();
    }

    public function employeeInBranch(int $employeeUserId, int $branchId): ?object
    {
        return DB::table('employee_details')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('employee_details.user_id', $employeeUserId)
            ->where('departments.branch_id', $branchId)
            ->whereNull('employee_details.deleted_at')
            ->select('employee_details.*')
            ->first();
    }

    public function createGeneral(array $data): int
    {
        return DB::table('tasks')->insertGetId([
            'company_id' => $data['company_id'],
            'employee_user_id' => $data['employee_user_id'],
            'supervisor_user_id' => $data['supervisor_user_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'type' => 'ad_hoc',
            'priority' => $data['priority'] ?? 'medium',
            'due_date' => $data['due_date'],
            'status' => 'pending',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function updateGeneral(int $id, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('tasks')->where('id', $id)->update($data);
    }

    public function softDeleteGeneral(int $id): void
    {
        DB::table('tasks')->where('id', $id)->update(['deleted_at' => Carbon::now()]);
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

    public function getTasksByEmployee(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Task::with('supervisor.profile')
            ->where('employee_user_id', $userId)
            ->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getHomeTasks(int $employeeUserId): Collection
    {
        return Task::where('employee_user_id', $employeeUserId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->orderBy('due_date', 'asc')
            ->limit(3)
            ->get();
    }

    public function findTaskByIdForEmployee(int $taskId, int $employeeUserId): ?Task
    {
        return Task::where('id', $taskId)
            ->where('employee_user_id', $employeeUserId)
            ->with('supervisor.profile')
            ->first();
    }
}