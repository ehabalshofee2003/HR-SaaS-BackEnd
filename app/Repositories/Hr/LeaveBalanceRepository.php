<?php

namespace App\Repositories\Hr;

use App\Models\Hr\LeaveBalance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveBalanceRepository
{
    // ================= دوال Branch Manager (جديدة) =================

    public function getForEmployeeInBranch(int $employeeUserId, int $branchId): array
    {
        return DB::table('leave_balances')
            ->join('employee_details', 'leave_balances.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->join('leave_policies', 'leave_balances.policy_id', '=', 'leave_policies.id')
            ->where('leave_balances.employee_user_id', $employeeUserId)
            ->where('departments.branch_id', $branchId)
            ->where('leave_balances.year', Carbon::now()->year)
            ->select(
                'leave_balances.id',
                'leave_balances.remaining_days',
                'leave_balances.year',
                'leave_policies.leave_type',
                'leave_policies.days_per_year'
            )
            ->get()
            ->toArray();
    }

    public function findBalance(int $employeeUserId, int $policyId, int $year): ?object
    {
        return DB::table('leave_balances')
            ->where('employee_user_id', $employeeUserId)
            ->where('policy_id', $policyId)
            ->where('year', $year)
            ->first();
    }

    public function deductDays(int $balanceId, float $days): void
    {
        DB::table('leave_balances')
            ->where('id', $balanceId)
            ->decrement('remaining_days', $days, ['updated_at' => Carbon::now()]);
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

    public function getEmployeeCurrentYearBalances(int $employeeUserId)
    {
        return LeaveBalance::where('employee_user_id', $employeeUserId)
            ->where('year', now()->year)
            ->with('policy')
            ->get();
    }
    public function getCombinedBalance(int $employeeUserId): array
    {
        $result = DB::table('leave_balances')
            ->join('leave_policies', 'leave_balances.policy_id', '=', 'leave_policies.id')
            ->where('leave_balances.employee_user_id', $employeeUserId)
            ->where('leave_balances.year', Carbon::now()->year)
            ->selectRaw('SUM(leave_policies.days_per_year) as total, SUM(leave_balances.remaining_days) as remaining')
            ->first();

    return [
        'total_days' => (int) ($result->total ?? 0),
        'remaining_days' => (int) ($result->remaining ?? 0),
    ];
    }
}