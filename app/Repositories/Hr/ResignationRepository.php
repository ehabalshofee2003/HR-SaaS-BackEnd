<?php

namespace App\Repositories\Hr;

use App\Models\Hr\Resignation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResignationRepository
{
    // ================= دوال Branch Manager (جديدة) =================

    public function paginateForBranch(int $branchId, array $filters, int $perPage = 15)
    {
        $query = DB::table('resignations')
            ->join('employee_details', 'resignations.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->join('user_profiles', 'resignations.employee_user_id', '=', 'user_profiles.user_id')
            ->where('departments.branch_id', $branchId)
            ->whereNull('resignations.deleted_at')
            ->select(
                'resignations.id',
                'resignations.reason',
                'resignations.notice_date',
                'resignations.last_working_date',
                'resignations.status',
                'resignations.created_at',
                'user_profiles.full_name as employee_name'
            );

        if (!empty($filters['status'])) {
            $query->where('resignations.status', $filters['status']);
        } else {
            $query->where('resignations.status', 'pending');
        }

        return $query->orderByDesc('resignations.created_at')->paginate($perPage);
    }

    public function findForBranch(int $id, int $branchId): ?object
    {
        return DB::table('resignations')
            ->join('employee_details', 'resignations.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('resignations.id', $id)
            ->where('departments.branch_id', $branchId)
            ->whereNull('resignations.deleted_at')
            ->select('resignations.*')
            ->first();
    }

    public function updateStatusForBranch(int $id, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('resignations')->where('id', $id)->update($data);
    }

    public function suspendUser(int $userId): void
    {
        DB::table('users')->where('id', $userId)->update([
            'status' => 'suspended',
            'updated_at' => Carbon::now(),
        ]);
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

    public function create(array $data): Resignation
    {
        return Resignation::create($data);
    }

    public function getEmployeeResignations(int $employeeUserId, int $perPage = 15)
    {
        return Resignation::where('employee_user_id', $employeeUserId)
            ->with('supervisor.profile')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findEmployeeResignationById(int $id, int $employeeUserId): ?Resignation
    {
        return Resignation::where('id', $id)
            ->where('employee_user_id', $employeeUserId)
            ->with('supervisor.profile')
            ->first();
    }

    public function updateStatus(Resignation $resignation, string $status): bool
    {
        return $resignation->update(['status' => $status]);
    }
}