<?php

namespace App\Repositories\Supervisor;

use App\Repositories\Interfaces\Supervisor\TaskManagementRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TaskManagementRepository implements TaskManagementRepositoryInterface
{
    public function list(int $supervisorId, array $filters): array
    {
        $query = DB::table('tasks as t')
            ->join('users as u', 'u.id', '=', 't.employee_user_id')
            ->join('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->where('t.supervisor_user_id', $supervisorId)
            ->whereNull('t.deleted_at');

        if (!empty($filters['status'])) {
            $query->where('t.status', $filters['status']);
        }

        return $query->orderByDesc('t.due_date')
            ->select(['t.id', 'p.full_name as employee_name', 't.title', 't.due_date', 't.priority', 't.status'])
            ->get()
            ->all();
    }

    public function find(int $id, int $supervisorId): ?object
    {
        return DB::table('tasks')
            ->where('id', $id)
            ->where('supervisor_user_id', $supervisorId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function create(array $data): int
    {
        return DB::table('tasks')->insertGetId(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function update(int $id, array $data): void
    {
        if (empty($data)) {
            return;
        }

        $data['updated_at'] = now();
        DB::table('tasks')->where('id', $id)->update($data);
    }

    public function delete(int $id): void
    {
        DB::table('tasks')->where('id', $id)->update(['deleted_at' => now()]);
    }
}