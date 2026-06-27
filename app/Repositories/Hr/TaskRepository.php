<?php

namespace App\Repositories\Hr;

use App\Models\Hr\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
class TaskRepository
{
    /**
     * جلب مهام موظف معين مع التصفية والترقيم
     */
    public function getTasksByEmployee(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Task::with('supervisor.profile')
            ->where('employee_user_id', $userId)
            ->latest();

        // فلترة حسب الحالة (status) إذا أرسلها الـ Flutter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Pagination: 15 مهمة في الصفحة (لحفظ الأداء والذاكرة)
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