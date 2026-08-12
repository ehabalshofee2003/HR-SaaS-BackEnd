<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BaseUserTestSeeder::class, // <-- يجب أن يكون أولاً دائماً
            SupervisorTestSeeder::class, // أضف هذا السطر
            PermissionSeeder::class,
            PayrollTestSeeder::class,
            AttendanceMonthTestSeeder::class,
            AttendanceTestSeeder::class,
            ResignationTestSeeder::class,
            AnnouncementTestSeeder::class,
            HrEpicTestSeeder::class, 
            TaskTestSeeder::class, // أضف هذا السطر
            EvaluationCriteriaTestSeeder::class, // أضف هذا السطر
            LeaveTypesTestSeeder::class, // أضف هذا السطر
            LeaveRequestTestSeeder::class,
            EmployeeThreeYearPayrollSeeder::class, // أضف هذا السطر
            ExceptionTypesSeeder::class,
            
        ]);

        $this->command->info('✅ تم تثبيت كل بيانات الاختبار بنجاح!');
    }
}