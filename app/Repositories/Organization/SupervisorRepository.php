<?php

namespace App\Repositories\Organization;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SupervisorRepository
{
    public function paginateForBranch(int $branchId, array $filters, int $perPage = 15)
    {
        $query = DB::table('users')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->leftJoin('departments', 'departments.supervisor_user_id', '=', 'users.id')
            ->where('users.branch_id', $branchId)
            ->where('users.user_type', 'supervisor')
            ->whereNull('users.deleted_at')
            ->select(
                'users.id',
                'user_profiles.full_name',
                'users.phone',
                'users.email',
                'users.status',
                'users.last_login_at',
                'users.created_at',
                'departments.id as department_id',
                'departments.name as department_name'
            );

        if (!empty($filters['status'])) {
            $query->where('users.status', $filters['status']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('departments.id', $filters['department_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('user_profiles.full_name', 'like', "%{$search}%")
                  ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('users.created_at')->paginate($perPage);
    }

    public function findForBranch(int $id, int $branchId): ?object
    {
        return DB::table('users')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->where('users.id', $id)
            ->where('users.branch_id', $branchId)
            ->where('users.user_type', 'supervisor')
            ->whereNull('users.deleted_at')
            ->select('users.*', 'user_profiles.full_name')
            ->first();
    }

    public function create(array $data): int
    {
        return DB::table('users')->insertGetId([
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password_hash' => $data['password_hash'],
            'user_type' => 'supervisor',
            'status' => 'active',
            'branch_id' => $data['branch_id'],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function createProfile(int $userId, string $fullName): void
    {
        DB::table('user_profiles')->insert([
            'user_id' => $userId,
            'full_name' => $fullName,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function assignToDepartment(int $supervisorUserId, int $departmentId): void
    {
        DB::table('departments')->where('id', $departmentId)->update([
            'supervisor_user_id' => $supervisorUserId,
            'updated_at' => Carbon::now(),
        ]);
    }

    public function unassignFromDepartment(int $supervisorUserId): void
    {
        DB::table('departments')->where('supervisor_user_id', $supervisorUserId)->update([
            'supervisor_user_id' => null,
            'updated_at' => Carbon::now(),
        ]);
    }

    public function currentDepartment(int $supervisorUserId): ?object
    {
        return DB::table('departments')
            ->where('supervisor_user_id', $supervisorUserId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function update(int $id, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('users')->where('id', $id)->update($data);
    }

    public function updateProfileName(int $userId, string $fullName): void
    {
        DB::table('user_profiles')->where('user_id', $userId)->update([
            'full_name' => $fullName,
            'updated_at' => Carbon::now(),
        ]);
    }

    public function softDelete(int $id): void
    {
        DB::table('users')->where('id', $id)->update(['deleted_at' => Carbon::now()]);
    }

    public function countSupervisedEmployees(int $supervisorUserId): int
    {
        return DB::table('employee_details')
            ->where('supervisor_id', $supervisorUserId)
            ->whereNull('deleted_at')
            ->count();
    }
}
