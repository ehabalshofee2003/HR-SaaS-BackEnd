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
    public function listForSupervisor(int $supervisorId, array $filters): array
    {
        $date = $filters['date'] ?? Carbon::today()->toDateString();

        $query = DB::table('attendance_logs as a')
            ->join('users as u', 'u.id', '=', 'a.employee_user_id')
            ->join('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->join('employee_details as ed', 'ed.user_id', '=', 'u.id')
            ->where('ed.supervisor_id', $supervisorId)
            ->whereDate('a.check_in', $date)
            ->whereNull('a.deleted_at')
            ->whereNull('ed.deleted_at')
            ->select([
                'a.id', 'p.full_name as employee_name', 'a.type',
                'a.status', 'a.check_in', 'a.check_out', 'a.work_hours',
            ]);

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('a.status', $filters['status']);
        }

        return $query->orderBy('a.check_in')->get()->all();
    }

    public function findForSupervisor(int $id, int $supervisorId): ?object
    {
        return DB::table('attendance_logs as a')
            ->join('employee_details as ed', 'ed.user_id', '=', 'a.employee_user_id')
            ->where('a.id', $id)
            ->where('ed.supervisor_id', $supervisorId)
            ->whereNull('a.deleted_at')
            ->select('a.*')
            ->first();
    }

    public function employeeBelongsToSupervisor(int $employeeUserId, int $supervisorId): bool
    {
        return DB::table('employee_details')
            ->where('user_id', $employeeUserId)
            ->where('supervisor_id', $supervisorId)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function update(int $id, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('attendance_logs')->where('id', $id)->update($data);
    }

    public function createManual(array $data): int
    {
        return DB::table('attendance_logs')->insertGetId(array_merge($data, [
            'type' => 'manual',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]));
    }
    public function findTodayLog(int $employeeUserId, string $date): ?object
    {
        return DB::table('attendance_logs')
            ->where('employee_user_id', $employeeUserId)
            ->whereDate('check_in', $date)
            ->whereNull('deleted_at')
            ->first();
    }
 
    public function monthlySummaryBySupervisor(int $supervisorId): array
    {
        $rows = DB::table('attendance_logs as a')
            ->join('employee_details as ed', 'ed.user_id', '=', 'a.employee_user_id')
            ->where('ed.supervisor_id', $supervisorId)
            ->whereMonth('a.check_in', Carbon::now()->month)
            ->whereYear('a.check_in', Carbon::now()->year)
            ->whereNull('a.deleted_at')
            ->select(['a.check_in', 'a.status'])
            ->get();

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $summary = [];
        foreach ($days as $day) {
            $summary[$day] = ['present' => 0, 'late' => 0, 'absent' => 0];
        }

        foreach ($rows as $row) {
            $dayName = Carbon::parse($row->check_in)->format('l');
            if (isset($summary[$dayName][$row->status])) {
                $summary[$dayName][$row->status]++;
            }
        }

        return collect($summary)->map(fn($counts, $day) => array_merge(['day' => $day], $counts))->values()->all();
    }
}