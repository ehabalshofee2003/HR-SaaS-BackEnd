<?php

namespace App\Repositories\Payroll;

use App\Models\Payroll\PayrollRecord;
use App\Models\Payroll\PayrollPeriod;

class PayrollRepository
{
    public function getEmployeePayrolls(int $employeeUserId, int $perPage = 15)
    {
        return PayrollRecord::where('employee_user_id', $employeeUserId)
            ->whereHas('period', function ($query) {
                $query->whereIn('status', ['approved', 'paid']);
            })
            ->with('period')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findEmployeePayrollById(int $id, int $employeeUserId): ?PayrollRecord
    {
        return PayrollRecord::where('id', $id)
            ->where('employee_user_id', $employeeUserId)
            ->whereHas('period', function ($query) {
                $query->whereIn('status', ['approved', 'paid']);
            })
            ->with(['period', 'details'])
            ->first();
    }
}