<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevTestDataSeeder extends Seeder
{
    /**
     * هذا الملف يجمع كل البيانات الوهمية الخاصة ببيئة التطوير فقط
     */
    public function run(): void
    {
        // الترتيب مهم هنا (لا توجد علاقات معقدة بينهم لكنه أفضل ممارسة)
        $this->call([
            PayrollTestSeeder::class,
            AttendanceTestSeeder::class,
            ResignationTestSeeder::class,
            AnnouncementTestSeeder::class,
        ]);

        $this->command->info('✅ ========================================');
        $this->command->info('✅ تم تثبيت كل بيانات الاختبار بنجاح!');
        $this->command->info('✅ ========================================');
    }
}