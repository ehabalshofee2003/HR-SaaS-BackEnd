<?php

namespace Database\Seeders;

use App\Models\Hr\Task;
use App\Models\Identity\User;
use App\Models\Identity\UserProfile;
use App\Models\Organization\Branch;
use App\Models\Organization\Company;
use App\Models\Organization\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
         // 1. إنشاء المستخدم المالك أولاً
    $owner = \App\Models\Identity\User::create([
        
        'phone' => '01000000000',
        'password_hash' => bcrypt('password123'),
        // ... باقي الحقول
    ]);

  
        // 1. إنشاء شركة
        $company = Company::create([
            'owner_user_id' => 1,
            'name' => 'STEP Company',
            'status' => 'active'
        ]);

        // 2. إنشاء مشرف (Supervisor)
        $supervisor = User::create([
            'email' => 'supervisor@STEP.com',
            'phone' => '01000000001',
            'password_hash' => Hash::make('password123'),
            'user_type' => 'supervisor',
            'status' => 'active'
        ]);
        UserProfile::create([
            'user_id' => $supervisor->id,
            'full_name' => 'Khaled Supervisor',
        ]);

        // 3. إنشاء فرع وقسم
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Main Branch',
            'status' => 'active'
        ]);
        $department = Department::create([
            'branch_id' => $branch->id,
            'supervisor_user_id' => $supervisor->id,
            'name' => 'Warehouses Dept',
            'status' => 'active'
        ]);

        // 4. إنشاء موظف (Employee)
        $employee = User::create([
            'email' => 'employee@badran.com',
            'phone' => '01000000002',
            'password_hash' => Hash::make('password123'),
            'user_type' => 'employee',
            'status' => 'active'
        ]);
        UserProfile::create([
            'user_id' => $employee->id,
            'full_name' => 'Ahmed Employee',
            'avatar' => null,
            'national_id' => '29812345678901',
            'date_of_birth' => '1998-05-15'
        ]);
        \App\Models\Identity\EmployeeDetail::create([
            'user_id' => $employee->id,
            'department_id' => $department->id,
            'job_title' => 'Warehouse Worker',
            'employment_status' => 'active',
            'hire_date' => '2023-01-10'
        ]);

        // 5. إنشاء مهام للموظف
        Task::create([
            'company_id' => $company->id,
            'employee_user_id' => $employee->id,
            'supervisor_user_id' => $supervisor->id,
            'title' => 'Organize Egg Cartons',
            'description' => 'Arrange the new egg cartons in Warehouse B.',
            'type' => 'daily',
            'due_date' => now()->addDays(2),
            'status' => 'pending',
            'reward_amount' => 50.00
        ]);

        Task::create([
            'company_id' => $company->id,
            'employee_user_id' => $employee->id,
            'supervisor_user_id' => $supervisor->id,
            'title' => 'Check Refrigerator Temperature',
            'description' => 'Log the temperature of fridge #3 every 2 hours.',
            'type' => 'ad_hoc',
            'due_date' => now()->addHours(8),
            'status' => 'in_progress',
            'reward_amount' => 0
        ]);
    }
}