<?php

namespace App\Repositories\Organization;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DepartmentRepository
{
    public function paginateForBranch(int $branchId, array $filters, int $perPage = 15)
    {
        $query = DB::table('departments')
            ->leftJoin('users', 'departments.supervisor_user_id', '=', 'users.id')
            ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->where('departments.branch_id', $branchId)
            ->whereNull('departments.deleted_at')
            ->select(
                'departments.id',
                'departments.name',
                'departments.status',
                'departments.created_at',
                'departments.supervisor_user_id',
                'user_profiles.full_name as supervisor_name'
            );

        if (!empty($filters['status'])) {
            $query->where('departments.status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('departments.name', 'like', "%{$search}%")
                  ->orWhere('user_profiles.full_name', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('departments.created_at')->paginate($perPage);
    }

    public function findForBranch(int $id, int $branchId): ?object
    {
        return DB::table('departments')
            ->where('id', $id)
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function nameExistsInBranch(int $branchId, string $name, ?int $excludeId = null): bool
    {
        $query = DB::table('departments')
            ->where('branch_id', $branchId)
            ->where('name', $name)
            ->whereNull('deleted_at');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function create(array $data): int
    {
        return DB::table('departments')->insertGetId([
            'branch_id' => $data['branch_id'],
            'supervisor_user_id' => $data['supervisor_user_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => 'active',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function update(int $id, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('departments')->where('id', $id)->update($data);
    }

    public function softDelete(int $id): void
    {
        DB::table('departments')->where('id', $id)->update([
            'deleted_at' => Carbon::now(),
        ]);
    }

    public function countEmployees(int $departmentId): int
    {
        return DB::table('employee_details')
            ->where('department_id', $departmentId)
            ->whereNull('deleted_at')
            ->count();
    }

    public function countActiveEmployees(int $departmentId): int
    {
        return DB::table('employee_details')
            ->where('department_id', $departmentId)
            ->where('employment_status', 'active')
            ->whereNull('deleted_at')
            ->count();
    }

    public function todayAttendanceCount(int $departmentId): int
    {
        return DB::table('attendance_logs')
            ->join('employee_details', 'attendance_logs.employee_user_id', '=', 'employee_details.user_id')
            ->where('employee_details.department_id', $departmentId)
            ->whereDate('attendance_logs.check_in', Carbon::today())
            ->whereIn('attendance_logs.status', ['present', 'late'])
            ->whereNull('employee_details.deleted_at')
            ->count();
    }

    public function supervisorBelongsToBranch(int $supervisorUserId, int $branchId): bool
    {
        return DB::table('users')
            ->where('id', $supervisorUserId)
            ->where('branch_id', $branchId)
            ->where('user_type', 'supervisor')
            ->whereNull('deleted_at')
            ->exists();
    }
}