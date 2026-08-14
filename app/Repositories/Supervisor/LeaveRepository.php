<?php

namespace App\Repositories\Supervisor;

use App\Repositories\Interfaces\Supervisor\LeaveRepositoryInterface;
use Illuminate\Support\Facades\DB;

class LeaveRepository implements LeaveRepositoryInterface
{
    public function balances(int $employeeUserId): array
    {
        return DB::table('leave_balances as lb')
            ->join('leave_types as lt', 'lt.id', '=', 'lb.leave_type_id')
            ->where('lb.employee_user_id', $employeeUserId)
            ->where('lb.year', now()->year)
            ->select(['lt.name as type_name', 'lt.max_days_per_year as total_days', 'lb.remaining_days'])
            ->get()
            ->all();
    }

    public function history(int $employeeUserId): array
    {
        return DB::table('leave_requests as lr')
            ->join('leave_types as lt', 'lt.id', '=', 'lr.leave_type_id')
            ->where('lr.employee_id', $employeeUserId)
            ->where('lr.status', '!=', 'pending')
            ->whereNull('lr.deleted_at')
            ->orderByDesc('lr.created_at')
            ->select(['lt.name as type_name', 'lr.start_date', 'lr.end_date', 'lr.status', 'lr.reason'])
            ->get()
            ->all();
    }
}