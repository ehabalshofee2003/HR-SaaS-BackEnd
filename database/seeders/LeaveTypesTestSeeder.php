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
        $company = Company::where('name', 'Badran Poultry Test')->first();

        if (!$company) {
            $this->command->error('الشركة التجريبية غير موجودة. شغّل BaseUserTestSeeder أولاً.');
            return;
        }

        $employeePhone = '0791234567';
        $employee = User::where('phone', $employeePhone)->first();

        $types = [
            [
                'code' => 'sick',
                'name' => 'إجازة مرضية',
                'description' => 'إجازة مرضية مدفوعة',
                'is_paid' => true,
                'max_days_per_year' => 14,
                'requires_attachment' => true,
                'days_per_year' => 14,
                'is_carry_forward' => false,
            ],
            [
                'code' => 'emergency',
                'name' => 'إجازة طارئة',
                'description' => 'إجازة طارئة مدفوعة',
                'is_paid' => true,
                'max_days_per_year' => 5,
                'requires_attachment' => false,
                'days_per_year' => 5,
                'is_carry_forward' => false,
            ],
            [
                'code' => 'unpaid',
                'name' => 'إجازة بدون راتب',
                'description' => 'إجازة غير مدفوعة، لا تُخصم من أي رصيد',
                'is_paid' => false,
                'max_days_per_year' => 30,
                'requires_attachment' => false,
                'days_per_year' => 0,
                'is_carry_forward' => false,
            ],
        ];

        foreach ($types as $type) {
            $leaveType = LeaveType::firstOrCreate(
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

            $policy = LeavePolicy::firstOrCreate(
                ['company_id' => $company->id, 'leave_type' => $type['code']],
                [
                    'days_per_year' => $type['days_per_year'],
                    'is_carry_forward' => $type['is_carry_forward'],
                ]
            );

            // رصيد ابتدائي للموظف التجريبي (باستثناء unpaid التي لا تحتاج رصيداً فعلياً)
            if ($employee && $type['code'] !== 'unpaid') {
                LeaveBalance::firstOrCreate(
                    [
                        'employee_user_id' => $employee->id,
                        'policy_id' => $policy->id,
                        'year' => now()->year,
                    ],
                    ['remaining_days' => $type['days_per_year']]
                );
            }
        }

        $this->command->warn('تم زرع أنواع وسياسات وأرصدة sick / emergency / unpaid بنجاح.');
    }
}