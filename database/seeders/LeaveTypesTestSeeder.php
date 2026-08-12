<?php

namespace Database\Seeders;

use App\Models\Organization\Company;
use App\Models\Hr\LeaveType;
use App\Models\Hr\LeavePolicy;
use App\Models\Hr\LeaveBalance;
use App\Models\Identity\User;
use Illuminate\Database\Seeder;

class LeaveTypesTestSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Nova Retail Group')->first();

        if (!$company) {
            $this->command->error('Test company not found. Run BaseUserTestSeeder first.');
            return;
        }

        $employee = User::where('phone', '0791234567')->first();

        $types = [
            ['code' => 'annual', 'name' => 'Annual Leave', 'description' => 'Paid annual leave', 'is_paid' => true, 'max_days_per_year' => 14, 'requires_attachment' => false, 'days_per_year' => 14],
            ['code' => 'sick', 'name' => 'Sick Leave', 'description' => 'Paid sick leave', 'is_paid' => true, 'max_days_per_year' => 14, 'requires_attachment' => true, 'days_per_year' => 14],
            ['code' => 'emergency', 'name' => 'Emergency Leave', 'description' => 'Paid emergency leave', 'is_paid' => true, 'max_days_per_year' => 5, 'requires_attachment' => false, 'days_per_year' => 5],
        ];

        foreach ($types as $type) {
            $leaveType = LeaveType::updateOrCreate(
                ['company_id' => $company->id, 'code' => $type['code']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'is_paid' => $type['is_paid'],
                    'max_days_per_year' => $type['max_days_per_year'],
                    'requires_attachment' => $type['requires_attachment'],
                    'is_active' => true,
                ]
            );

            $policy = LeavePolicy::updateOrCreate(
                ['company_id' => $company->id, 'leave_type' => $type['code']],
                ['days_per_year' => $type['days_per_year'], 'is_carry_forward' => false]
            );

            if ($employee) {
                LeaveBalance::updateOrCreate(
                    ['employee_user_id' => $employee->id, 'policy_id' => $policy->id, 'year' => now()->year],
                    ['remaining_days' => $type['days_per_year']]
                );
            }
        }

        $this->command->warn('✅ Leave types (English) seeded: Annual (14), Sick (14), Emergency (5).');
    }
}