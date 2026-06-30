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
        $user = User::where('phone', '0791234567')->first();
        if (!$user) {
            $this->command->error("المستخدم التجريبي غير موجود!");
            return;
        }

        $companyId = $user->getCurrentCompanyId();
        $branchId = $user->employeeDetail?->department?->branch_id;

        // إنشاء سجل حضور لليوم (ليظهر في الـ Dashboard)
        AttendanceLog::firstOrCreate(
            [
                'employee_user_id' => $user->id, 
                'check_in' => Carbon::today()->setHour(8)->setMinute(15)->toDateTimeString(), // 8:15 AM مثل الصورة
                'check_out' => null
            ],
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'work_hours' => 0.0, // التزامن بعدم تركه null
                'type' => 'qr',
                'status' => 'present'
            ]
        );

        $this->command->info("✅ تم إنشاء بيانات الحضور لليوم بنجاح!");
    }
}