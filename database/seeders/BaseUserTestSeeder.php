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
        $branchManagerPhone = '0798888888';
        $secondSupervisorPhone = '0797777777';

        if (User::where('phone', $employeePhone)->exists()) {
            $this->command->info('Test data already exists.');
            return;
        }

        $supervisor = User::create([
            'phone' => $supervisorPhone,
            'password_hash' => Hash::make('123456'),
            'user_type' => 'supervisor',
            'status' => 'active',
        ]);
        UserProfile::create(['user_id' => $supervisor->id, 'full_name' => 'James Carter']);

        $company = Company::create([
            'name' => 'Nova Retail Group',
            'owner_user_id' => $supervisor->id,
        ]);

        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Downtown Branch']);

        $branchManager = User::create([
            'phone' => $branchManagerPhone,
            'password_hash' => Hash::make('123456'),
            'user_type' => 'manager',
            'status' => 'active',
            'branch_id' => $branch->id,
        ]);
        UserProfile::create(['user_id' => $branchManager->id, 'full_name' => 'Michael Reed']);

        $department = Department::create([
            'branch_id' => $branch->id,
            'name' => 'Human Resources',
            'supervisor_user_id' => $supervisor->id,
        ]);

        $secondSupervisor = User::create([
            'phone' => $secondSupervisorPhone,
            'password_hash' => Hash::make('123456'),
            'user_type' => 'supervisor',
            'status' => 'active',
            'branch_id' => $branch->id,
        ]);
        UserProfile::create(['user_id' => $secondSupervisor->id, 'full_name' => 'Sarah Bennett']);

        $secondDepartment = Department::create([
            'branch_id' => $branch->id,
            'name' => 'Sales',
        ]);

        CompanySetting::firstOrCreate(
            ['company_id' => $company->id, 'key' => 'work_start_time'],
            ['value' => '08:00', 'type' => 'string']
        );

        $user = User::create([
            'phone' => $employeePhone,
            'password_hash' => Hash::make('123456'),
            'user_type' => 'employee',
            'status' => 'active',
        ]);
        UserProfile::create(['user_id' => $user->id, 'full_name' => 'Daniel Foster']);

        EmployeeDetail::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'job_title' => 'Software Engineer',
            'employment_status' => 'active',
            'hire_date' => now()->toDateString(),
            'supervisor_id' => $supervisor->id,
        ]);

        $this->command->warn('=====================================================');
        $this->command->warn('Test data created successfully!');
        $this->command->warn("Employee: {$employeePhone} | Password: 123456 | Daniel Foster");
        $this->command->warn("Supervisor: {$supervisorPhone} | Password: 123456 | James Carter");
        $this->command->warn("Branch Manager: {$branchManagerPhone} | Password: 123456 | Michael Reed");
        $this->command->warn("Second Supervisor: {$secondSupervisorPhone} | Password: 123456 | Sarah Bennett");
        $this->command->warn('=====================================================');
    }
}