<?php

namespace App\Repositories\Owner;

use Illuminate\Support\Facades\DB;

class BranchRepository
{
    public function getCompanyIdForOwner(int $ownerUserId): ?int
    {
        return DB::table('companies')
            ->where('owner_user_id', $ownerUserId)
            ->whereNull('deleted_at')
            ->value('id');
    }

    public function listForCompany(int $companyId, array $filters = [])
    {
        $query = DB::table('branches')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->orderBy('name')->get();
    }

    public function find(int $branchId, int $companyId): ?object
    {
        return DB::table('branches')
            ->where('id', $branchId)
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function nameExists(int $companyId, string $name, ?int $exceptId = null): bool
    {
        $query = DB::table('branches')
            ->where('company_id', $companyId)
            ->where('name', $name)
            ->whereNull('deleted_at');

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function create(array $data): int
    {
        return DB::table('branches')->insertGetId(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    public function update(int $branchId, array $data): void
    {
        DB::table('branches')->where('id', $branchId)->update(array_merge($data, [
            'updated_at' => now(),
        ]));
    }

    public function softDelete(int $branchId): void
    {
        DB::table('branches')->where('id', $branchId)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function getManagerForBranch(int $branchId): ?object
    {
        return DB::table('users')
            ->where('branch_id', $branchId)
            ->where('user_type', 'manager')
            ->whereNull('deleted_at')
            ->first();
    }

    public function managerExistsInCompany(int $managerId): ?object
    {
        return DB::table('users')
            ->where('id', $managerId)
            ->where('user_type', 'manager')
            ->whereNull('deleted_at')
            ->first();
    }

    public function unassignManagerFromBranch(int $branchId): void
    {
        DB::table('users')
            ->where('branch_id', $branchId)
            ->where('user_type', 'manager')
            ->update(['branch_id' => null, 'updated_at' => now()]);
    }

    public function assignManagerToBranch(int $managerId, int $branchId): void
    {
        DB::table('users')->where('id', $managerId)->update([
            'branch_id' => $branchId,
            'updated_at' => now(),
        ]);
    }

    public function countEmployees(int $branchId): int
    {
        return DB::table('employee_details')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->whereNull('departments.deleted_at')
            ->whereNull('employee_details.deleted_at')
            ->count();
    }

    public function countDepartments(int $branchId): int
    {
        return DB::table('departments')
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->count();
    }

    public function getDepartmentsForBranch(int $branchId)
    {
        return DB::table('departments')
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->get();
    }

    public function countEmployeesInDepartment(int $departmentId): int
    {
        return DB::table('employee_details')
            ->where('department_id', $departmentId)
            ->whereNull('deleted_at')
            ->count();
    }

    public function getUserFullName(?int $userId): ?string
    {
        if (!$userId) {
            return null;
        }

        return DB::table('user_profiles')
            ->where('user_id', $userId)
            ->value('full_name');
    }
}