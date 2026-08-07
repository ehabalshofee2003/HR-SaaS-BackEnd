<?php

namespace App\Repositories\Hr;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SupervisorAttendanceRepository
{
    public function isEmployeeInTeam(int $supervisorId, int $employeeId): bool
    {
        return DB::table('employee_details')
            ->where('user_id', $employeeId)
            ->where('supervisor_id', $supervisorId)
            ->exists();
    }

    public function getLastLogForToday(int $employeeId)
    {
        return DB::table('attendance_logs')
            ->where('employee_user_id', $employeeId)
            ->whereDate('check_in', Carbon::today())
            ->orderBy('check_in', 'desc')
            ->first();
    }

    public function getSupervisorContext(int $supervisorId)
    {
        return DB::table('users')
            ->join('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('users.id', $supervisorId)
            ->select('departments.branch_id', 'departments.company_id')
            ->first();
    }

    public function createCheckIn(int $employeeId, string $time, string $notes, $context, int $supervisorId)
    {
        DB::table('attendance_logs')->insert([
            'company_id' => $context->company_id,
            'employee_user_id' => $employeeId,
            'branch_id' => $context->branch_id,
            'check_in' => Carbon::parse($time)->format('Y-m-d H:i:s'), // تطبيق القاعدة الصارمة
            'type' => 'manual',
            'status' => Carbon::parse($time)->gt(Carbon::parse($time)->setTime(8, 30, 0)) ? 'late' : 'present', // حساب التأخير البسيط
            'notes' => $notes,
            'reviewed_by_supervisor' => $supervisorId,
            'reviewed_at_supervisor' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function updateCheckOut(int $logId, string $time, string $notes, int $supervisorId)
    {
        // 1. نجلب سجل الدخول لنحسب ساعات العمل
        $log = DB::table('attendance_logs')->where('id', $logId)->first();
        
        $workHours = 0;
        if ($log && $log->check_in) {
            // نحسب الفرق بين وقت الدخول ووقت الخروج بالدقائق، ثم نقسمها على 60
            $minutes = Carbon::parse($log->check_in)->diffInMinutes(Carbon::parse($time));
            $workHours = round($minutes / 60, 2);
        }

        // 2. تحديث السجل
        DB::table('attendance_logs')->where('id', $logId)->update([
            'check_out' => Carbon::parse($time)->format('Y-m-d H:i:s'),
            'work_hours' => $workHours, // الآن سيحفظ ساعات العمل بشكل صحيح
            'notes' => $notes,
            'reviewed_by_supervisor' => $supervisorId,
            'reviewed_at_supervisor' => now(),
            'updated_at' => now()
        ]);
    }

    public function getAttendanceLogs(int $supervisorId, array $filters)
    {
        $query = DB::table('attendance_logs')
            ->join('user_profiles', 'attendance_logs.employee_user_id', '=', 'user_profiles.user_id')
            ->join('employee_details', 'attendance_logs.employee_user_id', '=', 'employee_details.user_id')
            ->where('employee_details.supervisor_id', $supervisorId)
            ->select(
                'attendance_logs.id',
                'attendance_logs.employee_user_id',
                'user_profiles.full_name',
                'attendance_logs.check_in',
                'attendance_logs.check_out',
                'attendance_logs.work_hours',
                'attendance_logs.status',
                'attendance_logs.type'
            );

        if (!empty($filters['date'])) {
            $query->whereDate('attendance_logs.check_in', $filters['date']);
        } else {
            $query->whereDate('attendance_logs.check_in', Carbon::today()); // افتراضي اليوم
        }

        if (!empty($filters['employee_user_id'])) {
            $query->where('attendance_logs.employee_user_id', $filters['employee_user_id']);
        }

        return $query->orderBy('attendance_logs.check_in', 'desc')->paginate(15);
    }

    public function getLogByIdForSupervisor(int $supervisorId, int $logId)
    {
        return DB::table('attendance_logs')
            ->join('employee_details', 'attendance_logs.employee_user_id', '=', 'employee_details.user_id')
            ->where('employee_details.supervisor_id', $supervisorId)
            ->where('attendance_logs.id', $logId)
            ->select('attendance_logs.*')
            ->first();
    }

    public function updateLogTime(int $logId, string $field, string $time, ?string $notes, int $supervisorId)
    {
        $updateData = [
            $field => Carbon::parse($time)->format('Y-m-d H:i:s'),
            'reviewed_by_supervisor' => $supervisorId,
            'reviewed_at_supervisor' => now(),
            'updated_at' => now()
        ];
        
        if ($notes) $updateData['notes'] = $notes;

        DB::table('attendance_logs')->where('id', $logId)->update($updateData);
    }
}