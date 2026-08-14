<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Models\Identity\User;
use App\Services\Owner\PerformanceReportService;
use App\Services\Report\ReportPdfService;
use App\Exports\GenericReportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PerformanceReportController extends Controller
{
    public function __construct(
        private PerformanceReportService $reportService,
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

        $filters = $request->only(['branch_id', 'from', 'to']);
        $data = $this->reportService->getData($user->company_id, $filters);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function exportPdf(Request $request): Response
    {
        $user = $this->currentUser();
        $filters = $request->only(['branch_id', 'from', 'to']);
        $data = $this->reportService->getData($user->company_id, $filters);
        $company = DB::table('companies')->where('id', $user->company_id)->value('name');

        $content = $this->pdfService->generate(
            'تقرير تقييم الأداء',
            [
                'عدد التقييمات' => $data['summary']['total_evaluations'],
                'المتوسط' => $data['summary']['average_score'],
                'أعلى نتيجة' => $data['summary']['top_score'],
                'أقل نتيجة' => $data['summary']['lowest_score'],
            ],
            ['الموظف', 'الفرع', 'المشرف', 'من', 'إلى', 'النتيجة'],
            array_map(fn($r) => [
                $r['employee_name'], $r['branch_name'], $r['supervisor_name'],
                $r['period_start'], $r['period_end'], $r['overall_score'],
            ], $data['records']),
            $data['chart'],
            $company
        );

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="performance-report.pdf"',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $user = $this->currentUser();
        $filters = $request->only(['branch_id', 'from', 'to']);
        $data = $this->reportService->getData($user->company_id, $filters);

        $rows = array_map(fn($r) => [
            $r['employee_name'], $r['branch_name'], $r['supervisor_name'],
            $r['period_start'], $r['period_end'], $r['overall_score'],
        ], $data['records']);

        return Excel::download(
            new GenericReportExport($rows, ['الموظف', 'الفرع', 'المشرف', 'من', 'إلى', 'النتيجة'], 'تقييم الأداء'),
            'performance-report.xlsx'
        );
    }
}