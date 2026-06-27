<?php

namespace App\Services\Payroll;

use App\Models\Identity\User;
use App\Repositories\Payroll\PayrollRepository;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf; // نفترض وجود حزمة dompdf

class PayrollService
{
    public function __construct(private PayrollRepository $payrollRepository) {}

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
        $payroll = $this->payrollRepository->findEmployeePayrollById((int) $id, $user->id);

        if (!$payroll) {
            return [
                'success' => false,
                'message' => 'Payroll record not found.',
                'code' => 404,
                'data' => null
            ];
        }

        // إعداد البيانات للـ PDF (يجب أن تنشئ View في resources/views/pdfs/employee-payslip.blade.php)
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

    /**
     * قاعدة Auth Type Hinting الصارمة
     */
    private function getAuthenticatedUser(): User
    {
        $user = \App\Models\Identity\User::find(Auth::id());
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        return $user;
    }
}