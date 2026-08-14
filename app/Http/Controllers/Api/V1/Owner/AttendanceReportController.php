<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Models\Identity\User;
use App\Services\Owner\AttendanceReportService;
use App\Services\Report\ReportPdfService;
use App\Exports\GenericReportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceReportController extends Controller
{
    public function __construct(
        private AttendanceReportService $reportService,
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

        $filters = $request->only(['period', 'branch_id']);
        $data = $this->reportService->getData($user->company_id, $filters);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function exportPdf(Request $request): Response
    {
        $user = $this->currentUser();
        $filters = $request->only(['period', 'branch_id']);
        $data = $this->reportService->getData($user->company_id, $filters);

        $company = DB::table('companies')->where('id', $user->company_id)->value('name');

        $content = $this->pdfService->generate(
            'تقرير الحضور والانصراف',
            [
                'الحضور' => $data['summary']['total_present'],
                'التأخير' => $data['summary']['total_late'],
                'الغياب' => $data['summary']['total_absent'],
                'نسبة الحضور' => $data['summary']['attendance_rate'] . '%',
            ],
            ['الموظف', 'الفرع', 'التاريخ', 'الدخول', 'الخروج', 'ساعات العمل', 'الحالة'],
            array_map(fn($r) => [
                $r['employee_name'], $r['branch_name'], $r['date'],
                $r['check_in'], $r['check_out'] ?? '-', $r['work_hours'], $this->statusLabel($r['status']),
            ], $data['records']),
            $data['chart'],
            $company
        );

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="attendance-report.pdf"',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $user = $this->currentUser();
        $filters = $request->only(['period', 'branch_id']);
        $data = $this->reportService->getData($user->company_id, $filters);

        $rows = array_map(fn($r) => [
            $r['employee_name'], $r['branch_name'], $r['date'],
            $r['check_in'], $r['check_out'] ?? '-', $r['work_hours'], $this->statusLabel($r['status']),
        ], $data['records']);

        return Excel::download(
            new GenericReportExport($rows, ['الموظف', 'الفرع', 'التاريخ', 'الدخول', 'الخروج', 'ساعات العمل', 'الحالة'], 'تقرير الحضور'),
            'attendance-report.xlsx'
        );
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'present' => 'حاضر',
            'late' => 'متأخر',
            'absent' => 'غائب',
            'early_leave' => 'انصراف مبكر',
            default => $status,
        };
    }
}