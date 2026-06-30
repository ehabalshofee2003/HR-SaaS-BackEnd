<?php

namespace App\Repositories\Hr;

use App\Models\Hr\LeaveBalance;

class LeaveBalanceRepository
{
    public function getEmployeeCurrentYearBalances(int $employeeUserId)
    {
        return LeaveBalance::where('employee_user_id', $employeeUserId)
            ->where('year', now()->year)
            ->with('policy') // لسحب اسم النوع وعدد الأيام الكلي
            ->get();
    }
}