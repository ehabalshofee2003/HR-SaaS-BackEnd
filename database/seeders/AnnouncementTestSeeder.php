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
        $testUserId = 3; // <-- نفس ID الموظف
        $user = User::find($testUserId);
        $companyId = $user->getCurrentCompanyId();
        $branchId = $user->employeeDetail?->department?->branch_id;
        $departmentId = $user->employeeDetail?->department_id;

        if (!$companyId) {
            $this->command->error("المستخدم ليس لديه تسلسل هرمي!");
            return;
        }

        // 1. إعلان لكل الشركة (يجب أن يراه الموظف)
        Announcement::firstOrCreate(
            ['company_id' => $companyId, 'target_type' => 'all', 'title' => 'إعلان عام للاختبار'],
            [
                'created_by' => 1, // ضع ID المدير
                'content' => 'هذا محتوى الإعلان العام الذي يخص جميع الموظفين في الشركة.',
                'target_id' => null,
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->addDays(7)->toDateString(),
                'is_active' => true,
            ]
        );

        // 2. إعلان لفرع آخر (يجب ألا يراه الموظف - لاختبار الأمان)
        Announcement::firstOrCreate(
            ['company_id' => $companyId, 'target_type' => 'branch', 'title' => 'إعلان فرع وهمي'],
            [
                'created_by' => 1,
                'content' => 'محتوى يجب ألا يراه الموظف.',
                'target_id' => 99999, // فرع وهمي
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->addDays(7)->toDateString(),
                'is_active' => true,
            ]
        );

        $this->command->info("✅ تم إنشاء إعلانات اختبار (واحد مرئي وواحد مخفي)!");
    }
}