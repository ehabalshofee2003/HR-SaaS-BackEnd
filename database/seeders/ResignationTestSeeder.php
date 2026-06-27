<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use App\Models\Hr\Resignation;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ResignationTestSeeder extends Seeder
{
    public function run(): void
    {
        $testUserId = 3; // <-- نفس ID الموظف
        $user = User::find($testUserId);

        // نحتاج ID أي مشرف موجود في النظام (غالباً صاحب الشركة أو مدير فرع)
        // سنأخذ أول مستخدم آخر غير الموظف كـ مشرف وهمي للاختبار
        $supervisor = User::where('id', '!=', $testUserId)->first();

        if (!$supervisor) {
            $this->command->error("لا يوجد مستخدم آخر ليكون مشرفاً!");
            return;
        }

        Resignation::firstOrCreate(
            ['employee_user_id' => $testUserId, 'status' => 'pending'],
            [
                'supervisor_user_id' => $supervisor->id,
                'reason' => 'اختبار طلب استقالة من النظام',
                'notice_date' => Carbon::today()->toDateString(),
                'last_working_date' => Carbon::today()->addMonth()->toDateString(),
            ]
        );

        $this->command->info("✅ تم إنشاء طلب استقالة اختبار (Pending)!");
    }
}