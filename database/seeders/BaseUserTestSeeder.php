<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use App\Models\Identity\UserProfile;
use App\Models\Identity\EmployeeDetail;
use App\Models\Organization\Company;
use App\Models\Organization\Branch;
use App\Models\Organization\Department;
use App\Models\Hr\LeavePolicy;
use App\Models\Hr\LeaveBalance;
use App\Models\SaaS\CompanySetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BaseUserTestSeeder extends Seeder
{
    public function run(): void
    {
        $employeePhone = '0791234567';
        $supervisorPhone = '0799999999';

        if (User::where('phone', $employeePhone)->exists()) {
            $this->command->info('المستخدم التجريبي موجود مسبقاً.');
            return;
        }

        // 1. إنشاء المشرف (لأن الشركة تحتاج owner_user_id)
        $supervisor = User::create([
            'phone' => $supervisorPhone, 
            'password_hash' => Hash::make('123456'),
            'user_type' => 'supervisor', // حقل إلزامي من الـ Migration
            'status' => 'active',         // حقل إلزامي من الـ Migration
        ]);
        UserProfile::create([
            'user_id' => $supervisor->id, 
            'full_name' => 'Test Supervisor', // تم تصحيح الاسم ليطابق الجدول
        ]);

        // 2. الشركة (مع owner_user_id)
        $company = Company::create([
            'name' => 'Badran Poultry Test', 
            'owner_user_id' => $supervisor->id, 
        ]);

        // 3. الفرع
        $branch = Branch::create([
            'company_id' => $company->id, 
            'name' => 'Main Branch'
        ]);

        // 4. القسم
        $department = Department::create([
            'branch_id' => $branch->id, 
            'name' => 'HR Dept'
        ]);

        // 5. إعداد وقت الدوام
        CompanySetting::firstOrCreate(
            ['company_id' => $company->id, 'key' => 'work_start_time'], 
            ['value' => '08:00', 'type' => 'string']
        );

        // 6. إنشاء الموظف
        $user = User::create([
            'phone' => $employeePhone, 
            'password_hash' => Hash::make('123456'),
            'user_type' => 'employee',   // حقل إلزامي
            'status' => 'active',         // حقل إلزامي
        ]);
        UserProfile::create([
            'user_id' => $user->id, 
            'full_name' => 'Weaam Shakra', // تم تصحيح الاسم
        ]);
        
        // 7. تفاصيل الموظف (تم إزالة employee_id_number لأنه غير موجود في المايجريشن)
        EmployeeDetail::create([
            'user_id' => $user->id, 
            'department_id' => $department->id, 
            'job_title' => 'Software Engineer', 
            'employment_status' => 'active',    
            'hire_date' => now()->toDateString() 
        ]);
                // إنشاء نوع إجازة وهمي (مطلوب لإنشاء طلب الإجازة)
        \App\Models\Hr\LeaveType::firstOrCreate(
            ['company_id' => $company->id, 'code' => 'annual'],
            [
                'name' => 'إجازة سنوية',
                'description' => 'إجازة سنوية مدفوعة',
                'is_paid' => true,
                'max_days_per_year' => 15,
                'requires_attachment' => false,
                'is_active' => true,
            ]
        );
        // 8. سياسة الإجازات والرصيد
        $policy = LeavePolicy::create([
            'company_id' => $company->id,
            'leave_type' => 'annual',
            'days_per_year' => 15,
            'is_carry_forward' => true,
        ]);

        LeaveBalance::create([
            'employee_user_id' => $user->id,
            'policy_id' => $policy->id,
            'year' => now()->year,
            'remaining_days' => 12.5,
        ]);

        $this->command->warn("=====================================================");
        $this->command->warn("تم إنشاء البيانات الأساسية بنجاح!");
        $this->command->warn("Employee Phone: {$employeePhone} | Password: 123456");
        $this->command->warn("=====================================================");
    }
}