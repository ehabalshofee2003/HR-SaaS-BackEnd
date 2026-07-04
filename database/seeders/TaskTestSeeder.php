<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Identity\User;
use App\Models\Organization\Company;

class TaskTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * يعتمد هذا السيدر على وجود بيانات BaseUserTestSeeder (الموظف 0791234567 والمشرف 0799999999)
     */
    public function run(): void
    {
        // 1. سحب البيانات الأساسية لضمان صحة الـ Foreign Keys
        $employee = User::where('phone', '0791234567')->first();
        $supervisor = User::where('phone', '0799999999')->first();
        $company = Company::first();

        if (!$employee || !$supervisor || !$company || !$employee->employeeDetail) {
            $this->command->error('خطأ: يجب تشغيل BaseUserTestSeeder أولاً لإنشاء المستخدمين والشركة.');
            return;
        }

        $employeeDetailId = $employee->employeeDetail->id;
        $supervisorId = $supervisor->id;
        $companyId = $company->id;

        $now = Carbon::now();

        // 2. مصفوفة المهام الوهمية
        $tasks = [
            [
                'company_id'        => $companyId,
                'employee_user_id'  => $employee->id,
                'supervisor_user_id'=> $supervisorId,
                'title'             => 'مراجعة تقارير المبيعات اليومية',
                'description'       => 'مراجعة تقارير مبيعات الفترة الصباحية والتأكد من تطابقها مع النظام المحاسبي للشركة.',
                'type'              => 'daily',
                'status'            => 'pending',
                'priority'          => 'medium', // أولوية متوسطة
                'due_date'          => $now->copy()->addDay()->setTime(16, 0),
                'completed_at'      => null,
                'reward_amount'     => 0,
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            [
                'company_id'        => $companyId,
                'employee_user_id'  => $employee->id,
                'supervisor_user_id'=> $supervisorId,
                'title'             => 'تحضير عرض تقديمي لاجتماع الإدارة',
                'description'       => 'إعداد شرائح بوربوينت تحتوي على إنجازات قسم الموارد البشرية للربع الحالي وعرضها على المدير.',
                'type'              => 'ad_hoc',
                'status'            => 'in_progress',
                'priority'          => 'high', // أولوية عالية (لأنه فيه مكافأة ومهم)
                'due_date'          => $now->copy()->addDays(3)->setTime(12, 0),
                'completed_at'      => null,
                'reward_amount'     => 5000.00,
                'created_at'        => $now->copy()->subDay(),
                'updated_at'        => $now,
            ],
            [
                'company_id'        => $companyId,
                'employee_user_id'  => $employee->id,
                'supervisor_user_id'=> $supervisorId,
                'title'             => 'تسجيل حضور الورشة التدريبية',
                'description'       => 'تسجيل حضور الموظفين الذين حضروا ورشة الأمن السيبراني يدوياً نظراً لعطل في جهاز البصمة.',
                'type'              => 'daily',
                'status'            => 'completed',
                'priority'          => 'low', // أولوية منخفضة (لأنها مكتملة)
                'due_date'          => $now->copy()->subDay()->setTime(18, 0),
                'completed_at'      => $now->copy()->subDay()->setTime(17, 30),
                'reward_amount'     => 0,
                'created_at'        => $now->copy()->subDays(2),
                'updated_at'        => $now->copy()->subDay(),
            ],
            [
                'company_id'        => $companyId,
                'employee_user_id'  => $employee->id,
                'supervisor_user_id'=> $supervisorId,
                'title'             => 'إصلاح خطأ في حسابات المكافآت',
                'description'       => 'هناك خطأ في حساب مكافأة الموظفين الذين عملوا في العطل، يجب مراجعة الـ Logic في النظام وإصلاحه فوراً.',
                'type'              => 'ad_hoc',
                'status'            => 'pending',
                'priority'          => 'high', // أولوية عالية (متأخرة ومهمة)
                'due_date'          => $now->copy()->subDays(2)->setTime(10, 0),
                'completed_at'      => null,
                'reward_amount'     => 10000.00,
                'created_at'        => $now->copy()->subDays(5),
                'updated_at'        => $now->copy()->subDays(5),
            ],
        ];
        // 3. زراعة البيانات في قاعدة البيانات
        DB::table('tasks')->insert($tasks);
        
        $this->command->info('تم زراعة 4 مهام وهمية بنجاح (بما فيها مهمة متأخرة Overdue للاختبار).');
    }
}