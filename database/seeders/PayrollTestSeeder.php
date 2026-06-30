<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use App\Models\Payroll\PayrollPeriod;
use App\Models\Payroll\PayrollRecord;
use App\Models\Payroll\PayrollRecordDetail;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PayrollTestSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('phone', '0791234567')->first();
        if (!$user) {
            $this->command->error("المستخدم التجريبي غير موجود!");
            return;
        }

        $companyId = $user->getCurrentCompanyId();
        if (!$companyId) {
            $this->command->error("المستخدم ليس لديه تسلسل هرمي!");
            return;
        }

        // نستخدم الشهر الماضي لنجعله "آخر راتب"
        $lastMonth = now()->subMonth();

        $period = PayrollPeriod::firstOrCreate(
            ['company_id' => $companyId, 'month' => $lastMonth->month, 'year' => $lastMonth->year],
            [
                'start_date' => $lastMonth->startOfMonth()->toDateString(),
                'end_date' => $lastMonth->endOfMonth()->toDateString(),
                'status' => 'paid', // حالة مدفوعة لكي يظهر في الداشبورد
            ]
        );

        $record = PayrollRecord::firstOrCreate(
            ['employee_user_id' => $user->id, 'period_id' => $period->id],
            [
                'gross_salary' => 1500.00,
                'total_deductions' => 100.00,
                'total_bonuses' => 50.00,
                'net_salary' => 1450.00,
                'status' => 'paid',
                'approved_at' => $lastMonth->endOfMonth(),
                'paid_at' => $lastMonth->endOfMonth(),
            ]
        );

        $components = [
            ['name' => 'الراتب الأساسي', 'component_type' => 'base_salary', 'amount' => 1500.00],
            ['name' => 'بدل نقل', 'component_type' => 'allowance', 'amount' => 50.00],
            ['name' => 'خصم تأمينات', 'component_type' => 'deduction', 'amount' => 100.00],
        ];

        foreach ($components as $component) {
            PayrollRecordDetail::firstOrCreate(
                ['record_id' => $record->id, 'name' => $component['name']],
                ['component_type' => $component['component_type'], 'amount' => $component['amount']]
            );
        }

        $this->command->info("✅ تم إنشاء بيانات الرواتب بنجاح!");
    }
}