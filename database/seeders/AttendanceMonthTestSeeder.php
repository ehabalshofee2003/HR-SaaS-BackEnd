<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceMonthTestSeeder extends Seeder
{
    public function run(): void
    {
        $employee = User::where('phone', '0791234567')->first();

        if (!$employee) {
            $this->command->error('Test employee not found. Run BaseUserTestSeeder first.');
            return;
        }

        $companyId = $employee->getCurrentCompanyId();
        $branchId = $employee->employeeDetail?->department?->branch_id;

        if (!$companyId || !$branchId) {
            $this->command->error('Employee has no company/branch. Run BaseUserTestSeeder first.');
            return;
        }

        // نستهدف نفس الفترة يلي أنشأها PayrollTestSeeder (الشهر الماضي، حالة paid)
        // ليتطابق الاختبار مع الرد يلي شاركته
        $period = DB::table('payroll_periods')
            ->where('company_id', $companyId)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->where('status', 'paid')
            ->first();

        if (!$period) {
            $this->command->error('No paid payroll period found. Run PayrollTestSeeder first.');
            return;
        }

        $workStartTime = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->where('key', 'work_start_time')
            ->value('value') ?? '08:00';

        // احذف أي سجلات قديمة لنفس الشهر لتفادي التكرار عند إعادة التشغيل
        DB::table('attendance_logs')
            ->where('employee_user_id', $employee->id)
            ->whereBetween('check_in', [$period->start_date . ' 00:00:00', $period->end_date . ' 23:59:59'])
            ->delete();

        $start = Carbon::parse($period->start_date);
        $end = Carbon::parse($period->end_date);

        $absentDaysTarget = 2;
        $lateDaysTarget = 4;
        $absentCount = 0;
        $lateCount = 0;

        $current = $start->copy();
        $logs = [];

        while ($current->lte($end)) {
            // تخطي عطلة نهاية الأسبوع (سبت/أحد كافتراض قياسي — عدّلها إذا كانت عطلتكم مختلفة)
            if ($current->isWeekend()) {
                $current->addDay();
                continue;
            }

            $status = 'present';

            if ($absentCount < $absentDaysTarget && rand(1, 100) <= 12) {
                $status = 'absent';
                $absentCount++;
            } elseif ($lateCount < $lateDaysTarget && rand(1, 100) <= 20) {
                $status = 'late';
                $lateCount++;
            }

            if ($status === 'absent') {
                $logs[] = [
                    'company_id' => $companyId,
                    'employee_user_id' => $employee->id,
                    'branch_id' => $branchId,
                    'check_in' => $current->copy()->startOfDay()->toDateTimeString(),
                    'check_out' => null,
                    'work_hours' => 0,
                    'type' => 'manual',
                    'status' => 'absent',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } else {
                $workStart = Carbon::parse($current->format('Y-m-d') . ' ' . $workStartTime);

                if ($status === 'late') {
                    // تأخير عشوائي بين 10 و 75 دقيقة
                    $checkIn = $workStart->copy()->addMinutes(rand(10, 75));
                } else {
                    // حضور طبيعي، قد يسبق أو يلحق الموعد بدقائق قليلة
                    $checkIn = $workStart->copy()->addMinutes(rand(-5, 5));
                }

                $checkOut = $checkIn->copy()->addHours(8);
                $workHours = round($checkIn->diffInMinutes($checkOut) / 60, 2);

                $logs[] = [
                    'company_id' => $companyId,
                    'employee_user_id' => $employee->id,
                    'branch_id' => $branchId,
                    'check_in' => $checkIn->toDateTimeString(),
                    'check_out' => $checkOut->toDateTimeString(),
                    'work_hours' => $workHours,
                    'type' => 'qr',
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $current->addDay();
        }

        DB::table('attendance_logs')->insert($logs);

        $this->command->warn("✅ " . count($logs) . " attendance logs created for {$period->month}/{$period->year}.");
        $this->command->warn("Absent days: {$absentCount} | Late days: {$lateCount} | Present: " . (count($logs) - $absentCount - $lateCount));
    }
}