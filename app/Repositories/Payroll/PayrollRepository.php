<?php

namespace App\Repositories\Payroll;

use App\Models\Payroll\PayrollRecord;
use App\Models\Payroll\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollRepository
{
    // ================= دوال Branch Manager (جديدة) =================

    public function findOrCreatePeriod(int $companyId, int $month, int $year): object
    {
        $existing = DB::table('payroll_periods')
            ->where('company_id', $companyId)
            ->where('month', $month)
            ->where('year', $year)
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $id = DB::table('payroll_periods')->insertGetId([
            'company_id' => $companyId,
            'month' => $month,
            'year' => $year,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => 'draft',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return DB::table('payroll_periods')->where('id', $id)->first();
    }

    public function paginatePeriodsForBranch(int $branchId, array $filters, int $perPage = 15)
    {
        $query = DB::table('payroll_periods')
            ->join('payroll_records', 'payroll_periods.id', '=', 'payroll_records.period_id')
            ->join('employee_details', 'payroll_records.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->whereNull('payroll_periods.deleted_at')
            ->groupBy(
                'payroll_periods.id', 'payroll_periods.month', 'payroll_periods.year',
                'payroll_periods.status', 'payroll_periods.created_at'
            )
            ->select(
                'payroll_periods.id',
                'payroll_periods.month',
                'payroll_periods.year',
                'payroll_periods.status',
                'payroll_periods.created_at',
                DB::raw('COUNT(DISTINCT payroll_records.employee_user_id) as employee_count'),
                DB::raw('SUM(payroll_records.gross_salary) as total_basic'),
                DB::raw('SUM(payroll_records.total_deductions) as total_deductions'),
                DB::raw('SUM(payroll_records.total_bonuses) as total_additions'),
                DB::raw('SUM(payroll_records.net_salary) as total_net')
            );

        if (!empty($filters['year'])) {
            $query->where('payroll_periods.year', $filters['year']);
        }

        if (!empty($filters['status'])) {
            $query->where('payroll_periods.status', $filters['status']);
        }

        return $query->orderByDesc('payroll_periods.year')
            ->orderByDesc('payroll_periods.month')
            ->paginate($perPage);
    }

    public function findPeriodForBranch(int $periodId, int $branchId): ?object
    {
        return DB::table('payroll_periods')
            ->join('payroll_records', 'payroll_periods.id', '=', 'payroll_records.period_id')
            ->join('employee_details', 'payroll_records.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('payroll_periods.id', $periodId)
            ->where('departments.branch_id', $branchId)
            ->whereNull('payroll_periods.deleted_at')
            ->select('payroll_periods.*')
            ->distinct()
            ->first();
    }

    public function getRecordsForPeriodAndBranch(int $periodId, int $branchId): array
    {
        return DB::table('payroll_records')
            ->join('employee_details', 'payroll_records.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->join('user_profiles', 'payroll_records.employee_user_id', '=', 'user_profiles.user_id')
            ->where('payroll_records.period_id', $periodId)
            ->where('departments.branch_id', $branchId)
            ->select(
                'payroll_records.id',
                'payroll_records.employee_user_id',
                'payroll_records.gross_salary',
                'payroll_records.total_deductions',
                'payroll_records.total_bonuses',
                'payroll_records.net_salary',
                'payroll_records.status',
                'user_profiles.full_name as employee_name'
            )
            ->get()
            ->toArray();
    }

    public function findRecord(int $periodId, int $employeeUserId): ?object
    {
        return DB::table('payroll_records')
            ->where('period_id', $periodId)
            ->where('employee_user_id', $employeeUserId)
            ->first();
    }

    public function upsertRecord(int $periodId, int $employeeUserId, array $data): int
    {
        $existing = $this->findRecord($periodId, $employeeUserId);

        if ($existing) {
            DB::table('payroll_records')->where('id', $existing->id)->update(array_merge($data, [
                'updated_at' => Carbon::now(),
            ]));
            return $existing->id;
        }

        return DB::table('payroll_records')->insertGetId(array_merge($data, [
            'employee_user_id' => $employeeUserId,
            'period_id' => $periodId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]));
    }

    public function insertRecordDetail(int $recordId, string $name, string $type, float $amount): void
    {
        DB::table('payroll_record_details')->insert([
            'record_id' => $recordId,
            'name' => $name,
            'component_type' => $type,
            'amount' => $amount,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function clearRecordDetails(int $recordId): void
    {
        DB::table('payroll_record_details')->where('record_id', $recordId)->delete();
    }

    public function insertAdjustment(array $data): void
    {
        DB::table('payroll_adjustments')->insert([
            'payroll_record_id' => $data['payroll_record_id'],
            'created_by' => $data['created_by'],
            'adjustment_type' => $data['adjustment_type'],
            'amount' => $data['amount'],
            'reason' => $data['reason'],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function updateRecord(int $recordId, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('payroll_records')->where('id', $recordId)->update($data);
    }

    public function updatePeriodStatus(int $periodId, string $status): void
    {
        DB::table('payroll_periods')->where('id', $periodId)->update([
            'status' => $status,
            'updated_at' => Carbon::now(),
        ]);
    }

    public function updateAllRecordsStatus(int $periodId, int $branchId, string $status, array $extra = []): void
    {
        $employeeIds = DB::table('payroll_records')
            ->join('employee_details', 'payroll_records.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('payroll_records.period_id', $periodId)
            ->where('departments.branch_id', $branchId)
            ->pluck('payroll_records.employee_user_id');

        DB::table('payroll_records')
            ->where('period_id', $periodId)
            ->whereIn('employee_user_id', $employeeIds)
            ->update(array_merge(['status' => $status, 'updated_at' => Carbon::now()], $extra));
    }

    public function getActiveEmployeesInBranch(int $branchId): array
    {
        return DB::table('employee_details')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->where('employee_details.employment_status', 'active')
            ->whereNull('employee_details.deleted_at')
            ->select('employee_details.user_id', 'employee_details.basic_salary')
            ->get()
            ->toArray();
    }

    public function countAttendanceStatus(int $employeeUserId, string $startDate, string $endDate, string $status): int
    {
        return DB::table('attendance_logs')
            ->where('employee_user_id', $employeeUserId)
            ->whereBetween('check_in', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', $status)
            ->whereNull('deleted_at')
            ->count();
    }

    public function getCompanySetting(int $companyId, string $key): ?string
    {
        $setting = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->where('key', $key)
            ->first();

        return $setting->value ?? null;
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

public function getEmployeePayrolls(int $employeeUserId, int $perPage = 15)
{
    return PayrollRecord::where('employee_user_id', $employeeUserId)
        ->with(['period'])
        ->orderByDesc('id')
        ->paginate($perPage);
}

public function findEmployeePayrollById(int $id, int $employeeUserId): ?PayrollRecord
{
    return PayrollRecord::where('id', $id)
        ->where('employee_user_id', $employeeUserId)
        ->with(['period', 'details'])
        ->first();
}
public function findEmployeePayrollForPdf(int $id, int $employeeUserId): ?PayrollRecord
{
    return PayrollRecord::where('id', $id)
        ->where('employee_user_id', $employeeUserId)
        ->whereIn('status', ['approved', 'paid'])
        ->with(['period', 'details'])
        ->first();
}
}