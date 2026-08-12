<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use App\Models\Payroll\PayrollPeriod;
use App\Models\Payroll\PayrollRecord;
use App\Models\Payroll\PayrollRecordDetail;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EmployeeThreeYearPayrollSeeder extends Seeder
{
    public function run(): void
    {
        $employeePhone = '0791234567';
        $user = User::where('phone', $employeePhone)->first();

        if (!$user) {
            $this->command->error("Test employee ({$employeePhone}) not found. Run BaseUserTestSeeder first.");
            return;
        }

        $companyId = $user->getCurrentCompanyId();
        if (!$companyId) {
            $this->command->error('User has no associated company.');
            return;
        }

        $basicSalary = 1500.00;
        $totalMonths = 36;

        $this->command->info("Creating {$totalMonths} months (3 years) of payroll for {$user->phone}...");

        if ($user->employeeDetail) {
            $user->employeeDetail->update([
                'hire_date' => Carbon::now()->subMonths($totalMonths)->toDateString(),
            ]);
        }

        for ($monthsAgo = $totalMonths - 1; $monthsAgo >= 0; $monthsAgo--) {
            $targetMonth = Carbon::now()->subMonths($monthsAgo);

            if ($monthsAgo === 0) {
                $periodStatus = 'draft';
                $recordStatus = 'draft';
            } elseif ($monthsAgo === 1) {
                $periodStatus = 'approved';
                $recordStatus = 'approved';
            } else {
                $periodStatus = 'paid';
                $recordStatus = 'paid';
            }

            $period = PayrollPeriod::firstOrCreate(
                ['company_id' => $companyId, 'month' => $targetMonth->month, 'year' => $targetMonth->year],
                [
                    'start_date' => $targetMonth->copy()->startOfMonth()->toDateString(),
                    'end_date' => $targetMonth->copy()->endOfMonth()->toDateString(),
                    'status' => $periodStatus,
                ]
            );

            $deductions = round($basicSalary * (rand(0, 8) / 100), 2);
            $bonuses = rand(0, 4) === 0 ? round($basicSalary * 0.05, 2) : 0;
            $netSalary = round($basicSalary - $deductions + $bonuses, 2);

            $record = PayrollRecord::updateOrCreate(
                ['employee_user_id' => $user->id, 'period_id' => $period->id],
                [
                    'gross_salary' => $basicSalary,
                    'total_deductions' => $deductions,
                    'total_bonuses' => $bonuses,
                    'net_salary' => $netSalary,
                    'status' => $recordStatus,
                    'approved_at' => $recordStatus !== 'draft' ? $targetMonth->copy()->endOfMonth() : null,
                    'paid_at' => $recordStatus === 'paid' ? $targetMonth->copy()->endOfMonth()->addDays(2) : null,
                ]
            );

            PayrollRecordDetail::where('record_id', $record->id)->delete();

            PayrollRecordDetail::create([
                'record_id' => $record->id,
                'name' => 'Base Salary',
                'component_type' => 'base_salary',
                'amount' => $basicSalary,
            ]);

            if ($deductions > 0) {
                PayrollRecordDetail::create([
                    'record_id' => $record->id,
                    'name' => 'Insurance & Attendance Deduction',
                    'component_type' => 'deduction',
                    'amount' => $deductions,
                ]);
            }

            if ($bonuses > 0) {
                PayrollRecordDetail::create([
                    'record_id' => $record->id,
                    'name' => 'Performance Bonus',
                    'component_type' => 'bonus',
                    'amount' => $bonuses,
                ]);
            }
        }

        $this->command->warn("✅ Created {$totalMonths} months (3 years) of payroll for {$user->phone}.");
        $this->command->warn('Last month: draft (unpaid) | Before that: approved (not yet paid) | Rest: paid');
    }
}