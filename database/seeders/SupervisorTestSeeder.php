<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Identity\User;

class SupervisorTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. التأكد من وجود قسم واحد على الأقل (يفترض أن الـ Seeders الأساسية أنشأت الشركة والفرع والقسم)
        $department = DB::table('departments')->first();

        if (!$department) {
            $this->command->error('لا يوجد أقسام في قاعدة البيانات! تأكد من تشغيل الـ Seeders الأساسية أولاً.');
            return;
        }

        // 2. التأكد من عدم وجود هذا الحساب مسبقاً
        $existingUser = User::where('phone', '0999999999')->first();
        if ($existingUser) {
            $this->command->info('حساب المشرف التجريبي موجود مسبقاً.');
            return;
        }

        // 3. إنشاء حساب المشرف
        $supervisorId = DB::table('users')->insertGetId([
            'phone' => '0999999999',
            'password_hash' => Hash::make('12345678'), // استخدام الحقل الصحيح
            'user_type' => 'supervisor',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 4. إنشاء الملف الشخصي
        DB::table('user_profiles')->insert([
            'user_id' => $supervisorId,
            'full_name' => 'مشرف قسم تجريبي',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 5. إنشاء تفاصيل الوظيفة وربطه بالقسم (هذا هو السطر السحري الذي يحل كل مشاكلك)
        DB::table('employee_details')->insert([
            'user_id' => $supervisorId,
            'department_id' => $department->id, // ربطه بأول قسم موجود
            'supervisor_id' => null,             // المشرف لا يملك مشرف أعلى منه
            'job_title' => 'مشرف إنتاج',
            'employment_status' => 'active',
            'hire_date' => now(),
            'basic_salary' => 0,
            'contract_type' => 'full_time',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->command->info('تم إنشاء حساب المشرف التجريبي بنجاح!');
        $this->command->warn('الرقم: 0999999999');
        $this->command->warn('الباسورد: 12345678');
    }
}