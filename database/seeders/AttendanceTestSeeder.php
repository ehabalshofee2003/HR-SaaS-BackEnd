<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use App\Models\Hr\AttendanceLog;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceTestSeeder extends Seeder
{
    public function run(): void
    {
        $testUserId = 3; // <-- نفس ID الموظف الذي تختبر به
        $user = User::find($testUserId);
        $companyId = $user->getCurrentCompanyId();

        // 1. إنشاء سجل أمس (مكتمل - للتاريخ)
        AttendanceLog::firstOrCreate(
            ['employee_user_id' => $testUserId, 'check_in' => Carbon::yesterday()->setHour(8)->setMinute(0)->toDateTimeString()],
            [
                'company_id' => $companyId,
                'branch_id' => $user->employeeDetail?->department?->branch_id,
                'check_out' => Carbon::yesterday()->setHour(17)->setMinute(0)->toDateTimeString(),
                'work_hours' => 9.0,
                'type' => 'manual',
                'status' => 'present'
            ]
        );

        // 2. إنشاء سجل اليوم (بدون خروج - لاختبار Today و Check-out)
        AttendanceLog::firstOrCreate(
            ['employee_user_id' => $testUserId, 'check_in' => Carbon::today()->setHour(8)->setMinute(5)->toDateTimeString(), 'check_out' => null],
            [
                'company_id' => $companyId,
                'branch_id' => $user->employeeDetail?->department?->branch_id,
                'work_hours' => 0.0, // <-- تم التعديل هنا: 0.0 بدل null
                'type' => 'qr',
                'status' => 'present'
            ]
        );

        $this->command->info("✅ تم إنشاء بيانات اختبار الحضور بنجاح لليوم وأمس!");
    }
}