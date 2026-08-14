<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Models\Identity\User;
use App\Services\Owner\FinancialReportService;
use App\Services\Report\ReportPdfService;
use App\Exports\GenericReportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReportController extends Controller
{
    public function __construct(
        private FinancialReportService $reportService,
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

        $filters = $request->only(['month', 'year']);
        $data = $this->reportService->getData($user->company_id, $filters);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function exportPdf(Request $request): Response
    {
        $user = $this->currentUser();
        $filters = $request->only(['month', 'year']);
        $data = $this->reportService->getData($user->company_id, $filters);

        $company = DB::table('companies')->where('id', $user->company_id)->value('name');

        $content = $this->pdfService->generate(
            'التقرير المالي',
            [
                'الأساسي' => number_format($data['summary']['total_base_salary'], 2),
                'البدلات' => number_format($data['summary']['total_allowances'], 2),
                'المكافآت' => number_format($data['summary']['total_bonuses'], 2),
                'الاستقطاعات' => number_format($data['summary']['total_deductions'], 2),
                'الصافي' => number_format($data['summary']['net_total'], 2),
            ],
            ['الموظف', 'الأساسي', 'البدلات', 'المكافآت', 'الأوفرتايم', 'الاستقطاعات', 'الصافي'],
            array_map(fn($r) => [
                $r['employee_name'], number_format($r['base_salary'], 2), number_format($r['allowances'], 2),
                number_format($r['bonuses'], 2), number_format($r['overtime'], 2),
                number_format($r['deductions'], 2), number_format($r['net_salary'], 2),
            ], $data['records']),
            $data['chart'],
            $company
        );

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="financial-report.pdf"',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $user = $this->currentUser();
        $filters = $request->only(['month', 'year']);
        $data = $this->reportService->getData($user->company_id, $filters);

        $rows = array_map(fn($r) => [
            $r['employee_name'], $r['base_salary'], $r['allowances'],
            $r['bonuses'], $r['overtime'], $r['deductions'], $r['net_salary'],
        ], $data['records']);

        return Excel::download(
            new GenericReportExport($rows, ['الموظف', 'الأساسي', 'البدلات', 'المكافآت', 'الأوفرتايم', 'الاستقطاعات', 'الصافي'], 'التقرير المالي'),
            'financial-report.xlsx'
        );
    }
}