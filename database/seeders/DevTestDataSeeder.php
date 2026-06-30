<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BaseUserTestSeeder::class, // <-- يجب أن يكون أولاً دائماً
            PayrollTestSeeder::class,
            AttendanceTestSeeder::class,
            ResignationTestSeeder::class,
            AnnouncementTestSeeder::class,
            HrEpicTestSeeder::class, 

        ]);

        $this->command->info('✅ تم تثبيت كل بيانات الاختبار بنجاح!');
    }
}