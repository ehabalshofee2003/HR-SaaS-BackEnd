<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Models\Identity\User;
use App\Services\Owner\BranchComparisonReportService;
use App\Services\Report\ReportPdfService;
use App\Exports\GenericReportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class BranchComparisonReportController extends Controller
{
    public function __construct(
        private BranchComparisonReportService $reportService,
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

    public function index(): JsonResponse
    {
        $user = $this->currentUser();

        if (!$user || !$user->company_id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $data = $this->reportService->getData($user->company_id);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function exportPdf(): Response
    {
        $user = $this->currentUser();
        $data = $this->reportService->getData($user->company_id);
        $company = DB::table('companies')->where('id', $user->company_id)->value('name');

        $content = $this->pdfService->generate(
            'مقارنة الفروع',
            [
                'عدد الفروع' => count($data['records']),
            ],
            ['الفرع', 'عدد الموظفين', 'نسبة الحضور', 'طلبات معلّقة', 'رواتب الشهر'],
            array_map(fn($r) => [
                $r['branch_name'], $r['employees_count'], $r['attendance_rate'] . '%',
                $r['pending_exceptions'], number_format($r['monthly_payroll'], 2),
            ], $data['records']),
            $data['chart']['employees'],
            $company
        );

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="branch-comparison.pdf"',
        ]);
    }

    public function exportExcel()
    {
        $user = $this->currentUser();
        $data = $this->reportService->getData($user->company_id);

        $rows = array_map(fn($r) => [
            $r['branch_name'], $r['employees_count'], $r['attendance_rate'],
            $r['pending_exceptions'], $r['monthly_payroll'],
        ], $data['records']);

        return Excel::download(
            new GenericReportExport($rows, ['الفرع', 'عدد الموظفين', 'نسبة الحضور', 'طلبات معلّقة', 'رواتب الشهر'], 'مقارنة الفروع'),
            'branch-comparison.xlsx'
        );
    }
}