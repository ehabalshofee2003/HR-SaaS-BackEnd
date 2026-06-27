<?php

namespace App\Repositories\Hr;

use App\Models\Hr\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceRepository
{
    // دالة موجودة مسبقاً (للتذكير فقط)
    public function hasActiveCheckInToday(int $employeeUserId): bool
    {
        return AttendanceLog::where('employee_user_id', $employeeUserId)
            ->whereDate('check_in', Carbon::today())
            ->whereNull('check_out')
            ->exists();
    }

    // دالة موجودة مسبقاً
    public function create(array $data): AttendanceLog
    {
        return AttendanceLog::create($data);
    }

    // === الدوال الجديدة ===

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