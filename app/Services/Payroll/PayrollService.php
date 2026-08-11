<?php

namespace App\Services\Payroll;

use App\Models\Identity\User;
use App\Repositories\Payroll\PayrollRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;

class PayrollService
{
    public function __construct(private PayrollRepository $payrollRepository) {}

    // ================= دوال Branch Manager (جديدة) =================

    public function list(User $manager, array $filters)
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->payrollRepository->paginatePeriodsForBranch($branchId, $filters);
    }

    public function calculate(User $manager, int $month, int $year): array
    {
        $branchId = $manager->getCurrentBranchId();
        $companyId = $manager->getCurrentCompanyId();

        $period = $this->payrollRepository->findOrCreatePeriod($companyId, $month, $year);

        if (!in_array($period->status, ['draft', 'calculated'])) {
            throw new Exception('لا يمكن إعادة حساب كشف تم اعتماده أو دفعه بالفعل.');
        }

        $employees = $this->payrollRepository->getActiveEmployeesInBranch($branchId);

        if (empty($employees)) {
            throw new Exception('لا يوجد موظفون نشطون بهذا الفرع لحساب الرواتب لهم.');
        }

        $lateDeductionPercent = (float) ($this->payrollRepository->getCompanySetting($companyId, 'late_deduction_percent') ?? 0);
        $absenceFullDay = (bool) ($this->payrollRepository->getCompanySetting($companyId, 'absence_deduction_full_day') ?? true);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        $results = [];

        DB::transaction(function () use ($employees, $period, $startDate, $endDate, $daysInMonth, $lateDeductionPercent, $absenceFullDay, &$results) {
            foreach ($employees as $employee) {
                $dailyRate = $daysInMonth > 0 ? $employee->basic_salary / $daysInMonth : 0;

                $absentDays = $this->payrollRepository->countAttendanceStatus($employee->user_id, $startDate, $endDate, 'absent');
                $lateDays = $this->payrollRepository->countAttendanceStatus($employee->user_id, $startDate, $endDate, 'late');

                $absenceDeduction = $absentDays * ($absenceFullDay ? $dailyRate : $dailyRate / 2);
                $lateDeduction = $lateDays * ($dailyRate * $lateDeductionPercent / 100);
                $totalDeductions = round($absenceDeduction + $lateDeduction, 4);
                $netSalary = round($employee->basic_salary - $totalDeductions, 4);

                $recordId = $this->payrollRepository->upsertRecord($period->id, $employee->user_id, [
                    'gross_salary' => $employee->basic_salary,
                    'total_deductions' => $totalDeductions,
                    'total_bonuses' => 0,
                    'net_salary' => $netSalary,
                    'status' => 'draft',
                ]);

                $this->payrollRepository->clearRecordDetails($recordId);
                $this->payrollRepository->insertRecordDetail($recordId, 'الراتب الأساسي', 'base_salary', $employee->basic_salary);
                if ($absenceDeduction > 0) {
                    $this->payrollRepository->insertRecordDetail($recordId, "خصم غياب ({$absentDays} يوم)", 'deduction', $absenceDeduction);
                }
                if ($lateDeduction > 0) {
                    $this->payrollRepository->insertRecordDetail($recordId, "خصم تأخير ({$lateDays} مرة)", 'deduction', $lateDeduction);
                }

                $results[] = [
                    'employee_user_id' => $employee->user_id,
                    'basic_salary' => $employee->basic_salary,
                    'absent_days' => $absentDays,
                    'late_days' => $lateDays,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $netSalary,
                ];
            }

            $this->payrollRepository->updatePeriodStatus($period->id, 'calculated');
        });

        return [
            'period' => $period,
            'entries' => $results,
        ];
    }

    public function getDetails(int $periodId, User $manager): array
    {
        $branchId = $manager->getCurrentBranchId();
        $period = $this->payrollRepository->findPeriodForBranch($periodId, $branchId);

        if (!$period) {
            throw new Exception('كشف الراتب غير موجود.', 404);
        }

        $records = $this->payrollRepository->getRecordsForPeriodAndBranch($periodId, $branchId);

        return ['period' => $period, 'records' => $records];
    }

    public function updateEntry(int $periodId, int $employeeUserId, array $data, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $period = $this->payrollRepository->findPeriodForBranch($periodId, $branchId);

        if (!$period) {
            throw new Exception('كشف الراتب غير موجود.', 404);
        }

        if ($period->status === 'approved' || $period->status === 'paid' || $period->status === 'closed') {
            throw new Exception('لا يمكن تعديل كشف تم اعتماده أو دفعه — يتطلب موافقة المالك.');
        }

        $record = $this->payrollRepository->findRecord($periodId, $employeeUserId);
        if (!$record) {
            throw new Exception('سجل الموظف غير موجود بهذا الكشف.', 404);
        }

        $updateData = array_intersect_key($data, array_flip(['gross_salary', 'total_deductions', 'total_bonuses']));

        $gross = $updateData['gross_salary'] ?? $record->gross_salary;
        $deductions = $updateData['total_deductions'] ?? $record->total_deductions;
        $bonuses = $updateData['total_bonuses'] ?? $record->total_bonuses;
        $updateData['net_salary'] = round($gross - $deductions + $bonuses, 4);

        $this->payrollRepository->updateRecord($record->id, $updateData);

        $this->payrollRepository->insertAdjustment([
            'payroll_record_id' => $record->id,
            'created_by' => $manager->id,
            'adjustment_type' => 'correction',
            'amount' => 0,
            'reason' => $data['reason'],
        ]);

        return $this->payrollRepository->findRecord($periodId, $employeeUserId);
    }

    public function addException(int $periodId, int $employeeUserId, array $data, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $period = $this->payrollRepository->findPeriodForBranch($periodId, $branchId);

        if (!$period) {
            throw new Exception('كشف الراتب غير موجود.', 404);
        }

        if (in_array($period->status, ['approved', 'paid', 'closed'])) {
            throw new Exception('لا يمكن إضافة استثناء لكشف تم اعتماده أو دفعه.');
        }

        $record = $this->payrollRepository->findRecord($periodId, $employeeUserId);
        if (!$record) {
            throw new Exception('سجل الموظف غير موجود بهذا الكشف.', 404);
        }

        return DB::transaction(function () use ($record, $data, $manager) {
            $this->payrollRepository->insertAdjustment([
                'payroll_record_id' => $record->id,
                'created_by' => $manager->id,
                'adjustment_type' => $data['adjustment_type'],
                'amount' => $data['amount'],
                'reason' => $data['reason'],
            ]);

            if ($data['adjustment_type'] === 'bonus') {
                $newBonuses = $record->total_bonuses + $data['amount'];
                $netSalary = round($record->gross_salary - $record->total_deductions + $newBonuses, 4);
                $this->payrollRepository->updateRecord($record->id, [
                    'total_bonuses' => $newBonuses,
                    'net_salary' => $netSalary,
                ]);
                $this->payrollRepository->insertRecordDetail($record->id, $data['reason'], 'bonus', $data['amount']);
            } elseif ($data['adjustment_type'] === 'deduction') {
                $newDeductions = $record->total_deductions + $data['amount'];
                $netSalary = round($record->gross_salary - $newDeductions + $record->total_bonuses, 4);
                $this->payrollRepository->updateRecord($record->id, [
                    'total_deductions' => $newDeductions,
                    'net_salary' => $netSalary,
                ]);
                $this->payrollRepository->insertRecordDetail($record->id, $data['reason'], 'deduction', $data['amount']);
            }

            return DB::table('payroll_records')->where('id', $record->id)->first();
        });
    }

    public function approve(int $periodId, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $period = $this->payrollRepository->findPeriodForBranch($periodId, $branchId);

        if (!$period) {
            throw new Exception('كشف الراتب غير موجود.', 404);
        }

        if ($period->status !== 'calculated') {
            throw new Exception('يجب حساب الكشف أولاً قبل الاعتماد.');
        }

        DB::transaction(function () use ($periodId, $branchId, $manager) {
            $this->payrollRepository->updatePeriodStatus($periodId, 'approved');
            $this->payrollRepository->updateAllRecordsStatus($periodId, $branchId, 'approved', [
                'approved_by' => $manager->id,
                'approved_at' => Carbon::now(),
                'locked_at' => Carbon::now(),
            ]);
        });

        // TODO: إرسال كشف فردي لكل موظف (Push + Email)

        return $this->payrollRepository->findPeriodForBranch($periodId, $branchId);
    }

    public function markAsPaid(int $periodId, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $period = $this->payrollRepository->findPeriodForBranch($periodId, $branchId);

        if (!$period) {
            throw new Exception('كشف الراتب غير موجود.', 404);
        }

        if ($period->status !== 'approved') {
            throw new Exception('يجب اعتماد الكشف أولاً قبل تسجيله كمدفوع.');
        }

        DB::transaction(function () use ($periodId, $branchId) {
            $this->payrollRepository->updatePeriodStatus($periodId, 'paid');
            $this->payrollRepository->updateAllRecordsStatus($periodId, $branchId, 'paid', [
                'paid_at' => Carbon::now(),
            ]);
        });

        return $this->payrollRepository->findPeriodForBranch($periodId, $branchId);
    }

    public function getExportData(int $periodId, User $manager): array
    {
        return $this->getDetails($periodId, $manager);
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

    public function getPayrolls()
    {
        $user = $this->getAuthenticatedUser();
        return $this->payrollRepository->getEmployeePayrolls($user->id);
    }

    public function getPayrollDetail($id)
    {
        $user = $this->getAuthenticatedUser();
        $payroll = $this->payrollRepository->findEmployeePayrollById((int) $id, $user->id);

        if (!$payroll) {
            return [
                'success' => false,
                'message' => 'Payroll record not found.',
                'code' => 404,
                'data' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Payroll details retrieved successfully.',
            'code' => 200,
            'data' => $payroll
        ];
    }

public function generatePdf($id)
{
    $user = $this->getAuthenticatedUser();

    // نتحقق أولاً هل السجل موجود أصلاً (بغض النظر عن الحالة) لتمييز 404 عن "لم يُعتمد بعد"
    $exists = $this->payrollRepository->findEmployeePayrollById((int) $id, $user->id);

    if (!$exists) {
        return [
            'success' => false,
            'message' => 'Payroll record not found.',
            'code' => 404,
            'data' => null
        ];
    }

    $payroll = $this->payrollRepository->findEmployeePayrollForPdf((int) $id, $user->id);

    if (!$payroll) {
        return [
            'success' => false,
            'message' => 'لا يمكن تحميل كشف راتب لم يُعتمد بعد من مدير الفرع.',
            'code' => 422,
            'data' => null
        ];
    }

    $data = [
        'payroll' => $payroll,
        'employee' => $user->load(['profile', 'employeeDetail.department.branch.company'])
    ];

    $pdf = Pdf::loadView('pdfs.employee-payslip', $data);

    return [
        'success' => true,
        'message' => 'PDF generated successfully.',
        'code' => 200,
        'data' => $pdf
    ];
}

    private function getAuthenticatedUser(): User
    {
        $user = \App\Models\Identity\User::find(Auth::id());
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        return $user;
    }
}