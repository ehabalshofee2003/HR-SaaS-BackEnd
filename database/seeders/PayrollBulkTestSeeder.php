<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use App\Models\Identity\UserProfile;
use App\Models\Identity\EmployeeDetail;
use App\Models\Organization\Company;
use App\Models\Organization\Department;
use App\Models\Payroll\PayrollPeriod;
use App\Models\Payroll\PayrollRecord;
use App\Models\Payroll\PayrollRecordDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PayrollBulkTestSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Badran Poultry Test')->first();

        if (!$company) {
            $this->command->error('الشركة التجريبية غير موجودة. شغّل BaseUserTestSeeder أولاً.');
            return;
        }

        $departments = Department::where('branch_id', function ($query) use ($company) {
            $query->select('id')->from('branches')->where('company_id', $company->id)->limit(1);
        })->get();

        if ($departments->isEmpty()) {
            $this->command->error('لا توجد أقسام بالشركة التجريبية.');
            return;
        }

        $jobTitles = ['Sales Rep', 'Accountant', 'Cashier', 'Warehouse Staff', 'Driver', 'Customer Service', 'HR Assistant', 'Technician'];
        $firstNames = ['Ahmad', 'Mohammad', 'Sara', 'Layla', 'Omar', 'Nour', 'Rami', 'Dina', 'Khaled', 'Maya', 'Yousef', 'Reem', 'Bilal', 'Hala', 'Fadi'];
        $lastNames = ['Al-Ahmad', 'Hassan', 'Khalil', 'Saleh', 'Youssef', 'Ibrahim', 'Nasser', 'Aziz', 'Mustafa', 'Karim'];

        $employeeCount = 25;
        $createdEmployees = [];

        $this->command->info("جاري إنشاء {$employeeCount} موظف وهمي...");

        for ($i = 1; $i <= $employeeCount; $i++) {
            $phone = '097' . str_pad((string) (1000000 + $i), 7, '0', STR_PAD_LEFT);

            if (User::where('phone', $phone)->exists()) {
                continue; // تفادي التكرار عند إعادة تشغيل السيدر
            }

            $department = $departments->random();
            $fullName = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
            $basicSalary = rand(400, 1200);

            $user = User::create([
                'phone' => $phone,
                'password_hash' => Hash::make('123456'),
                'user_type' => 'employee',
                'status' => 'active',
                'branch_id' => $department->branch_id,
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'full_name' => $fullName,
            ]);

            $employeeDetail = EmployeeDetail::create([
                'user_id' => $user->id,
                'department_id' => $department->id,
                'supervisor_id' => $department->supervisor_user_id,
                'job_title' => $jobTitles[array_rand($jobTitles)],
                'contract_type' => 'full_time',
                'basic_salary' => $basicSalary,
                'employment_status' => 'active',
                'hire_date' => Carbon::now()->subMonths(rand(6, 24))->toDateString(),
            ]);

            $createdEmployees[] = ['user' => $user, 'detail' => $employeeDetail, 'salary' => $basicSalary];
        }

        $this->command->info('تم إنشاء ' . count($createdEmployees) . ' موظف. جاري إنشاء رواتب 6 أشهر لكل منهم...');

        // إنشاء رواتب لآخر 6 أشهر، بحالات مختلفة لاختبار كل السيناريوهات
        for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
            $targetMonth = Carbon::now()->subMonths($monthsAgo);

            // آخر شهر يبقى Draft (لم يُعتمد بعد)، قبله Approved، الباقي Paid
            $periodStatus = $monthsAgo === 0 ? 'draft' : ($monthsAgo === 1 ? 'approved' : 'paid');
            $recordStatus = $periodStatus;

            $period = PayrollPeriod::firstOrCreate(
                ['company_id' => $company->id, 'month' => $targetMonth->month, 'year' => $targetMonth->year],
                [
                    'start_date' => $targetMonth->copy()->startOfMonth()->toDateString(),
                    'end_date' => $targetMonth->copy()->endOfMonth()->toDateString(),
                    'status' => $periodStatus,
                ]
            );

            foreach ($createdEmployees as $emp) {
                $deductions = round($emp['salary'] * (rand(0, 10) / 100), 2);
                $bonuses = rand(0, 3) === 0 ? round($emp['salary'] * 0.05, 2) : 0;
                $netSalary = round($emp['salary'] - $deductions + $bonuses, 2);

                $record = PayrollRecord::firstOrCreate(
                    ['employee_user_id' => $emp['user']->id, 'period_id' => $period->id],
                    [
                        'gross_salary' => $emp['salary'],
                        'total_deductions' => $deductions,
                        'total_bonuses' => $bonuses,
                        'net_salary' => $netSalary,
                        'status' => $recordStatus,
                        'approved_at' => $recordStatus !== 'draft' ? $targetMonth->copy()->endOfMonth() : null,
                        'paid_at' => $recordStatus === 'paid' ? $targetMonth->copy()->endOfMonth()->addDays(3) : null,
                    ]
                );

                PayrollRecordDetail::firstOrCreate(
                    ['record_id' => $record->id, 'name' => 'الراتب الأساسي'],
                    ['component_type' => 'base_salary', 'amount' => $emp['salary']]
                );

                if ($deductions > 0) {
                    PayrollRecordDetail::firstOrCreate(
                        ['record_id' => $record->id, 'name' => 'خصومات متنوعة'],
                        ['component_type' => 'deduction', 'amount' => $deductions]
                    );
                }

                if ($bonuses > 0) {
                    PayrollRecordDetail::firstOrCreate(
                        ['record_id' => $record->id, 'name' => 'مكافأة أداء'],
                        ['component_type' => 'bonus', 'amount' => $bonuses]
                    );
                }
            }
        }

        $this->command->warn('✅ تم إنشاء ' . count($createdEmployees) . ' موظف وهمي + رواتب 6 أشهر لكل منهم بنجاح.');
        $this->command->warn('كلمة مرور كل الموظفين الوهميين: 123456');
    }
}