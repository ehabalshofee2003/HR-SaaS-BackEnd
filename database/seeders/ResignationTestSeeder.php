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
        $user = User::where('phone', '0791234567')->first();
        $supervisor = User::where('phone', '0799999999')->first();

        if (!$user || !$supervisor) {
            $this->command->error("المستخدم التجريبي أو المشرف غير موجود!");
            return;
        }

        Resignation::firstOrCreate(
            ['employee_user_id' => $user->id, 'status' => 'pending'],
            [
                'supervisor_user_id' => $supervisor->id,
                'reason' => 'اختبار طلب استقالة من النظام',
                'notice_date' => Carbon::today()->toDateString(),
                'last_working_date' => Carbon::today()->addMonth()->toDateString(),
            ]
        );

        $this->command->info("✅ تم إنشاء طلب الاستقالة الاختباري بنجاح!");
    }
}