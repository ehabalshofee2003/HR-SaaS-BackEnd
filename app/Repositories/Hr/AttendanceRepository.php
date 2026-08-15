<?php

namespace App\Repositories\Hr;

use App\Models\Hr\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceRepository
{
    // ================= دوال Branch Manager =================

    public function paginateForBranch(int $branchId, array $filters, int $perPage = 20)
    {
        $query = DB::table('attendance_logs')
            ->join('users', 'attendance_logs.employee_user_id', '=', 'users.id')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->join('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('attendance_logs.branch_id', $branchId)
            ->whereNull('attendance_logs.deleted_at')
            ->select(
                'attendance_logs.id',
                'attendance_logs.check_in',
                'attendance_logs.check_out',
                'attendance_logs.work_hours',
                'attendance_logs.status',
                'attendance_logs.type',
                'attendance_logs.notes',
                'user_profiles.full_name as employee_name',
                'employee_details.department_id',
                'departments.name as department_name'
            );

        if (!empty($filters['date'])) {
            $query->whereDate('attendance_logs.check_in', $filters['date']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('employee_details.department_id', $filters['department_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('attendance_logs.status', $filters['status']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('attendance_logs.employee_user_id', $filters['employee_id']);
        }

        return $query->orderByDesc('attendance_logs.check_in')->paginate($perPage);
    }

    public function getForExport(int $branchId, array $filters): array
    {
        $query = DB::table('attendance_logs')
            ->join('users', 'attendance_logs.employee_user_id', '=', 'users.id')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->join('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('attendance_logs.branch_id', $branchId)
            ->whereNull('attendance_logs.deleted_at')
            ->select(
                'attendance_logs.check_in',
                'attendance_logs.check_out',
                'attendance_logs.work_hours',
                'attendance_logs.status',
                'user_profiles.full_name as employee_name',
                'departments.name as department_name'
            );

        if (!empty($filters['date'])) {
            $query->whereDate('attendance_logs.check_in', $filters['date']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('employee_details.department_id', $filters['department_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('attendance_logs.status', $filters['status']);
        }

        return $query->orderByDesc('attendance_logs.check_in')->get()->toArray();
    }

    public function findForBranch(int $id, int $branchId): ?object
    {
        return DB::table('attendance_logs')
            ->where('id', $id)
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function employeeBelongsToBranch(int $employeeUserId, int $branchId): bool
    {
        return DB::table('employee_details')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('employee_details.user_id', $employeeUserId)
            ->where('departments.branch_id', $branchId)
            ->whereNull('employee_details.deleted_at')
            ->exists();
    }

    /**
     * إدخال يدوي من Branch Manager فقط — منفصلة تماماً عن create() العامة
     * لتفادي تعارضها مع تدفق QR Check-In الخاص بالموظف.
     */
    public function createManualEntry(array $data): int
    {
        return DB::table('attendance_logs')->insertGetId([
            'company_id' => $data['company_id'],
            'employee_user_id' => $data['employee_user_id'],
            'branch_id' => $data['branch_id'],
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'] ?? null,
            'work_hours' => $data['work_hours'] ?? 0,
            'type' => 'manual',
            'status' => $data['status'],
            'notes' => $data['reason'],
            'reviewed_by_manager' => $data['reviewed_by_manager'],
            'reviewed_at_manager' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function update(int $id, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('attendance_logs')->where('id', $id)->update($data);
    }

    public function payrollPeriodStatusForDate(int $companyId, string $date): ?string
    {
        $carbonDate = Carbon::parse($date);

        $period = DB::table('payroll_periods')
            ->where('company_id', $companyId)
            ->where('month', $carbonDate->month)
            ->where('year', $carbonDate->year)
            ->whereNull('deleted_at')
            ->first();

        return $period->status ?? null;
    }

    public function logAudit(array $data): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $data['user_id'],
            'company_id' => $data['company_id'],
            'action' => $data['action'],
            'entity_type' => 'attendance_logs',
            'entity_id' => $data['entity_id'],
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'created_at' => Carbon::now(),
        ]);
    }

    // ================= دوال Employee/Supervisor Mobile (الأصلية — لم تُمس) =================

    public function hasActiveCheckInToday(int $employeeUserId): bool
    {
        return AttendanceLog::where('employee_user_id', $employeeUserId)
            ->whereDate('check_in', Carbon::today())
            ->whereNull('check_out')
            ->exists();
    }
public function hasCheckedInToday(int $employeeUserId): bool
{
    return AttendanceLog::where('employee_user_id', $employeeUserId)
        ->whereDate('check_in', Carbon::today())
        ->exists();
}
    /**
     * دالة عامة لإنشاء سجل حضور (تُستخدم من QR Check-In في تطبيق الموظف).
     * لا تُعدّل هذه الدالة لإضافة منطق خاص بمدير الفرع — استخدم createManualEntry() بدلاً من ذلك.
     */
    public function create(array $data): AttendanceLog
    {
        return AttendanceLog::create($data);
    }

    public function getTodayLog(int $employeeUserId): ?AttendanceLog
    {
        return AttendanceLog::where('employee_user_id', $employeeUserId)
            ->whereDate('check_in', Carbon::today())
            ->first();
    }

    public function findActiveCheckInToday(int $employeeUserId): ?AttendanceLog
    {
        return AttendanceLog::where('employee_user_id', $employeeUserId)
            ->whereDate('check_in', Carbon::today())
            ->whereNull('check_out')
            ->first();
    }

    public function updateCheckOut(AttendanceLog $log, string $checkOutTime, float $workHours): bool
    {
        return $log->update([
            'check_out' => $checkOutTime,
            'work_hours' => $workHours,
        ]);
    }

    public function getHistory(int $employeeUserId, int $perPage = 15)
    {
        return AttendanceLog::where('employee_user_id', $employeeUserId)
            ->orderByDesc('check_in')
            ->paginate($perPage);
    }
}