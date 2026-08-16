<?php

namespace App\Repositories\Supervisor;

use Illuminate\Support\Facades\DB;

class LeaveRequestRepository
{
    public function paginateForSupervisor(int $supervisorId, array $filters, int $perPage = 15)
    {
        $query = DB::table('leave_requests')
            ->join('users', 'leave_requests.employee_id', '=', 'users.id')
            ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->join('employee_details', 'employee_details.user_id', '=', 'users.id')
            ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->where('employee_details.supervisor_id', $supervisorId)
            ->whereNull('leave_requests.deleted_at')
            ->select(
                'leave_requests.*',
                'user_profiles.full_name as employee_name',
                'leave_types.name as leave_type_name'
            );

        if (!empty($filters['status'])) {
            $query->where('leave_requests.status', $filters['status']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('leave_requests.employee_id', $filters['employee_id']);
        }

        return $query->orderByRaw("FIELD(leave_requests.status, 'pending') DESC")
            ->orderByDesc('leave_requests.created_at')
            ->paginate($perPage);
    }

    public function findForSupervisor(int $id, int $supervisorId): ?object
    {
        return DB::table('leave_requests')
            ->join('users', 'leave_requests.employee_id', '=', 'users.id')
            ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->join('employee_details', 'employee_details.user_id', '=', 'users.id')
            ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->where('leave_requests.id', $id)
            ->where('employee_details.supervisor_id', $supervisorId)
            ->whereNull('leave_requests.deleted_at')
            ->select(
                'leave_requests.*',
                'user_profiles.full_name as employee_name',
                'leave_types.name as leave_type_name'
            )
            ->first();
    }

    public function getBalanceForEmployee(int $employeeId, int $leaveTypeId): ?object
    {
        return DB::table('leave_balances')
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->first();
    }

    public function updateStatus(int $id, array $data): void
    {
        DB::table('leave_requests')->where('id', $id)->update(array_merge($data, [
            'updated_at' => now(),
        ]));
    }

    public function deductBalance(int $employeeId, int $leaveTypeId, int $days): void
    {
        DB::table('leave_balances')
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->decrement('remaining_days', $days);
    }
}