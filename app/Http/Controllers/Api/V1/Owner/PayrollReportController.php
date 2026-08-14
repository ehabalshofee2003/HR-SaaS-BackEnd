<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Models\Identity\User;
use App\Services\Owner\PayrollReportService;
use App\Services\Report\ReportPdfService;
use App\Exports\GenericReportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PayrollReportController extends Controller
{
    public function __construct(
        private PayrollReportService $reportService,
        private ReportPdfService $pdfService,
    ) {}

    private function currentUser(): ?User
    {
        $userId = Auth::id();

        if (!$userId) {
            return null;
        }

        return User::find($userId);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        if (!$user || !$user->company_id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $filters = $request->only(['month', 'year', 'branch_id']);
        $data = $this->reportService->getData($user->company_id, $filters);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function exportPdf(Request $request): Response
    {
        $user = $this->currentUser();
        $filters = $request->only(['month', 'year', 'branch_id']);
        $data = $this->reportService->getData($user->company_id, $filters);

        $company = DB::table('companies')->where('id', $user->company_id)->value('name');

        $content = $this->pdfService->generate(
            'تقرير الرواتب',
            [
                'إجمالي الرواتب الأساسية' => number_format($data['summary']['total_gross'], 2),
                'إجمالي الاستقطاعات' => number_format($data['summary']['total_deductions'], 2),
                'إجمالي المكافآت' => number_format($data['summary']['total_bonuses'], 2),
                'صافي الرواتب' => number_format($data['summary']['total_net'], 2),
            ],
            ['الموظف', 'الفرع', 'الراتب الأساسي', 'الاستقطاعات', 'المكافآت', 'الصافي', 'الحالة'],
            array_map(fn($r) => [
                $r['employee_name'], $r['branch_name'],
                number_format($r['gross_salary'], 2), number_format($r['total_deductions'], 2),
                number_format($r['total_bonuses'], 2), number_format($r['net_salary'], 2),
                $this->statusLabel($r['status']),
            ], $data['records']),
            $data['chart'],
            $company
        );

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="payroll-report.pdf"',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $user = $this->currentUser();
        $filters = $request->only(['month', 'year', 'branch_id']);
        $data = $this->reportService->getData($user->company_id, $filters);

        $rows = array_map(fn($r) => [
            $r['employee_name'], $r['branch_name'],
            $r['gross_salary'], $r['total_deductions'], $r['total_bonuses'], $r['net_salary'],
            $this->statusLabel($r['status']),
        ], $data['records']);

        return Excel::download(
            new GenericReportExport($rows, ['الموظف', 'الفرع', 'الراتب الأساسي', 'الاستقطاعات', 'المكافآت', 'الصافي', 'الحالة'], 'تقرير الرواتب'),
            'payroll-report.xlsx'
        );
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'مسودة',
            'approved' => 'معتمد',
            'paid' => 'مدفوع',
            default => $status,
        };
    }
}