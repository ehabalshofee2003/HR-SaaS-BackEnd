<?php

namespace App\Repositories\Hr;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeRepository
{
    public function paginateForBranch(int $branchId, array $filters, int $perPage = 15)
    {
        $query = DB::table('users')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->join('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->leftJoin('user_profiles as supervisor_profiles', function ($join) {
                $join->on('employee_details.supervisor_id', '=', DB::raw('supervisor_profiles.user_id'));
            })
            ->where('departments.branch_id', $branchId)
            ->where('users.user_type', 'employee')
            ->whereNull('users.deleted_at')
            ->whereNull('employee_details.deleted_at')
            ->select(
                'users.id',
                'user_profiles.full_name',
                'users.phone',
                'users.status',
                'users.last_login_at',
                'employee_details.department_id',
                'departments.name as department_name',
                'employee_details.supervisor_id',
                'supervisor_profiles.full_name as supervisor_name',
                'employee_details.basic_salary',
                'employee_details.employment_status',
                'employee_details.hire_date'
            );

        if (!empty($filters['status'])) {
            $query->where('users.status', $filters['status']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('employee_details.department_id', $filters['department_id']);
        }

        if (!empty($filters['supervisor_id'])) {
            $query->where('employee_details.supervisor_id', $filters['supervisor_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('user_profiles.full_name', 'like', "%{$search}%")
                  ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('employee_details.hire_date')->paginate($perPage);
    }

    public function findForBranch(int $id, int $branchId): ?object
    {
        return DB::table('users')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->join('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('users.id', $id)
            ->where('departments.branch_id', $branchId)
            ->where('users.user_type', 'employee')
            ->whereNull('users.deleted_at')
            ->whereNull('employee_details.deleted_at')
            ->select('users.*', 'user_profiles.*', 'employee_details.*', 'users.id as id')
            ->first();
    }

    public function departmentBelongsToBranch(int $departmentId, int $branchId): ?object
    {
        return DB::table('departments')
            ->where('id', $departmentId)
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function createUser(array $data): int
    {
        return DB::table('users')->insertGetId([
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password_hash' => $data['password_hash'],
            'user_type' => 'employee',
            'status' => 'active',
            'branch_id' => $data['branch_id'] ?? null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function createProfile(int $userId, array $data): void
    {
        DB::table('user_profiles')->insert([
            'user_id' => $userId,
            'full_name' => $data['full_name'],
            'avatar' => $data['avatar'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'address' => $data['address'] ?? null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function createEmployeeDetail(int $userId, array $data): void
    {
        DB::table('employee_details')->insert([
            'user_id' => $userId,
            'department_id' => $data['department_id'],
            'supervisor_id' => $data['supervisor_id'] ?? null,
            'job_title' => $data['job_title'],
            'contract_type' => $data['contract_type'],
            'basic_salary' => $data['basic_salary'],
            'employment_status' => 'active',
            'hire_date' => $data['hire_date'],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function createDocument(array $data): void
    {
        DB::table('user_documents')->insert([
            'company_id' => $data['company_id'],
            'documentable_type' => 'App\\Models\\Identity\\User',
            'documentable_id' => $data['user_id'],
            'type' => $data['type'],
            'file_name' => $data['file_name'],
            'file_path' => $data['file_path'],
            'mime_type' => $data['mime_type'] ?? null,
            'uploaded_by' => $data['uploaded_by'],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function updateUser(int $id, array $data): void
    {
        if (empty($data)) return;
        $data['updated_at'] = Carbon::now();
        DB::table('users')->where('id', $id)->update($data);
    }

    public function updateProfile(int $userId, array $data): void
    {
        if (empty($data)) return;
        $data['updated_at'] = Carbon::now();
        DB::table('user_profiles')->where('user_id', $userId)->update($data);
    }

    public function updateEmployeeDetail(int $userId, array $data): void
    {
        if (empty($data)) return;
        $data['updated_at'] = Carbon::now();
        DB::table('employee_details')->where('user_id', $userId)->update($data);
    }

    public function softDelete(int $id): void
    {
        DB::table('users')->where('id', $id)->update(['deleted_at' => Carbon::now()]);
        DB::table('employee_details')->where('user_id', $id)->update(['deleted_at' => Carbon::now()]);
    }

    public function hasPaidPayrollRecords(int $userId): bool
    {
        return DB::table('payroll_records')
            ->where('employee_user_id', $userId)
            ->where('status', 'paid')
            ->exists();
    }

    public function listDocuments(int $userId): array
    {
        return DB::table('user_documents')
            ->where('documentable_type', 'App\\Models\\Identity\\User')
            ->where('documentable_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    public function findDocument(int $documentId, int $userId): ?object
    {
        return DB::table('user_documents')
            ->where('id', $documentId)
            ->where('documentable_type', 'App\\Models\\Identity\\User')
            ->where('documentable_id', $userId)
            ->first();
    }

    public function deleteDocument(int $documentId): void
    {
        DB::table('user_documents')->where('id', $documentId)->delete();
    }
}