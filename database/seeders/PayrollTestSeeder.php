<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use App\Models\Payroll\PayrollPeriod;
use App\Models\Payroll\PayrollRecord;
use App\Models\Payroll\PayrollRecordDetail;
use Illuminate\Database\Seeder;

class PayrollTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. حدد الـ ID الخاص بموظف الاختبار الخاص بك
        $testUserId = 3; // <-- غيّره إلى ID الموظف الذي تسجل دخوله من Postman

        $user = User::find($testUserId);

        if (!$user) {
            $this->command->error("المستخدم رقم {$testUserId} غير موجود في جدول users!");
            return;
        }

        // 2. سحب الـ company_id باستخدام التسلسل الهرمي المتفق عليه
        $companyId = $user->getCurrentCompanyId();

        if (!$companyId) {
            $this->command->error("المستخدم {$testUserId} ليس لديه تسلسل هرمي صحيح (يجب أن يكون مربوطاً بـ EmployeeDetail -> Department -> Branch -> Company)");
            return;
        }

        $this->command->info("تم العثور على Company ID: {$companyId}");

        // 3. إنشاء أو جلب فترة الرواتب (Payroll Period) للحالة approved
        // 3. إنشاء أو جلب فترة الرواتب (Payroll Period) للحالة approved
        $period = PayrollPeriod::firstOrCreate(
            [
                'company_id' => $companyId,
                'month' => now()->month,
                'year' => now()->year,
            ],
            [
                'start_date' => now()->startOfMonth()->toDateString(), // إضافة تاريخ البداية
                'end_date'   => now()->endOfMonth()->toDateString(),   // إضافة تاريخ النهاية
                'status' => 'approved',
            ]
        );
        // 4. إنشاء كشف الراتب (Payroll Record)
        $record = PayrollRecord::firstOrCreate(
            [
                'employee_user_id' => $user->id,
                'period_id' => $period->id,
            ],
            [
                'gross_salary' => 1500.00,
                'total_deductions' => 100.00,
                'total_bonuses' => 50.00,
                'net_salary' => 1450.00,
                'status' => 'approved',
                'approved_at' => now(),
            ]
        );

        // 5. إضافة تفاصيل الكشف (المكونات)
        $components = [
            ['name' => 'الراتب الأساسي', 'component_type' => 'base_salary', 'amount' => 1500.00],
            ['name' => 'بدل نقل', 'component_type' => 'allowance', 'amount' => 50.00],
            ['name' => 'خصم تأمينات', 'component_type' => 'deduction', 'amount' => 100.00],
        ];

        foreach ($components as $component) {
            PayrollRecordDetail::firstOrCreate(
                [
                    'record_id' => $record->id,
                    'name' => $component['name'],
                ],
                [
                    'component_type' => $component['component_type'],
                    'amount' => $component['amount'],
                ]
            );
        }

        $this->command->info("✅ تم إنشاء بيانات الاختبار بنجاح!");
        $this->command->warn("استخدم الـ Record ID التالي في Postman: {$record->id}");
        $this->command->warn("تأكد أنك تسجل الدخول بـ User ID: {$testUserId}");
    }
}