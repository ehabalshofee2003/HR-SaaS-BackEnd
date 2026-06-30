<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use App\Models\Support\Announcement;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AnnouncementTestSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('phone', '0791234567')->first();
        if (!$user) {
            $this->command->error("المستخدم التجريبي غير موجود!");
            return;
        }

        $companyId = $user->getCurrentCompanyId();
        $supervisor = User::where('phone', '0799999999')->first();

        Announcement::firstOrCreate(
            ['company_id' => $companyId, 'target_type' => 'all', 'title' => 'إعلان هام: توزيع الحوافز'],
            [
                'created_by' => $supervisor ? $supervisor->id : $user->id,
                'content' => 'تم اعتماد حوافز نهاية الشهر لجميع موظفي الفرع الرئيسي. يرجى مراجعة كشوف الرواتب.',
                'target_id' => null,
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->addDays(7)->toDateString(),
                'is_active' => true,
            ]
        );

        $this->command->info("✅ تم إنشاء بيانات الإعلانات بنجاح!");
    }
}