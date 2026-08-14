<?php

namespace App\Repositories\Supervisor;

use App\Repositories\Interfaces\Supervisor\EmployeeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    private function baseQuery(int $supervisorId)
    {
        return DB::table('employee_details as ed')
            ->join('users as u', 'u.id', '=', 'ed.user_id')
            ->join('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->join('departments as d', 'd.id', '=', 'ed.department_id')
            ->where('ed.supervisor_id', $supervisorId)
            ->whereNull('ed.deleted_at');
    }

    public function list(int $supervisorId): array
    {
        return $this->baseQuery($supervisorId)
            ->select(['u.id', 'p.full_name', 'ed.basic_salary'])
            ->get()
            ->all();
    }

    public function find(int $id, int $supervisorId): ?object
    {
        return $this->baseQuery($supervisorId)
            ->where('u.id', $id)
            ->select([
                'u.id', 'p.full_name', 'u.phone', 'ed.basic_salary', 'ed.hire_date',
                'ed.contract_type', 'p.national_id', 'd.name as department_name',
            ])
            ->first();
    }

    public function updateProfile(int $userId, array $employeeData): void
    {
        if (empty($employeeData)) {
            return;
        }

        $employeeData['updated_at'] = now();
        DB::table('employee_details')->where('user_id', $userId)->update($employeeData);
    }

    public function todayAttendance(int $employeeUserId): ?object
    {
        return DB::table('attendance_logs')
            ->where('employee_user_id', $employeeUserId)
            ->whereDate('check_in', Carbon::today())
            ->whereNull('deleted_at')
            ->first();
    }

    public function hasApprovedLeaveToday(int $employeeUserId): bool
    {
        return DB::table('leave_requests')
            ->where('employee_id', $employeeUserId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->whereNull('deleted_at')
            ->exists();
    }
}