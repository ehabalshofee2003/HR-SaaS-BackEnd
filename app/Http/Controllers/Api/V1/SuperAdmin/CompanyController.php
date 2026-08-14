<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\CreateCompanyRequest;
use App\Http\Requests\SuperAdmin\UpdateCompanyRequest;
use App\Services\Report\ReportPdfService;
use App\Services\SuperAdmin\CompanyService;
use App\Exports\GenericReportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;

class CompanyController extends Controller
{
    public function __construct(
        private CompanyService $companyService,
        private ReportPdfService $pdfService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'plan_id', 'sort_by', 'sort_dir', 'page', 'per_page']);
        $result = $this->companyService->list($filters);

        return response()->json(['success' => true, 'data' => $result['data'], 'meta' => $result['meta']]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->companyService->get($id)]);
    }

    public function store(CreateCompanyRequest $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->companyService->create($request->validated())], 201);
    }

    public function update(UpdateCompanyRequest $request, int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->companyService->update($id, $request->validated())]);
    }

    public function suspend(int $id): JsonResponse
    {
        return response()->json($this->companyService->suspend($id));
    }

    public function activate(int $id): JsonResponse
    {
        return response()->json($this->companyService->activate($id));
    }

    public function destroy(int $id): JsonResponse
    {
        return response()->json($this->companyService->delete($id));
    }

    public function exportPdf(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'plan_id']);
        $filters['per_page'] = 1000;
        $result = $this->companyService->list($filters);

        $content = $this->pdfService->generate(
            'قائمة الشركات',
            ['إجمالي الشركات' => $result['meta']['total']],
            ['اسم الشركة', 'المالك', 'الهاتف', 'الخطة', 'الحالة', 'الفروع', 'الموظفون'],
            array_map(fn($c) => [
                $c['name'], $c['owner_name'], $c['owner_phone'], $c['plan_name'] ?? '-',
                $c['status'], $c['branches_count'], $c['employees_count'],
            ], $result['data']),
            [],
            'منصة HR-SaaS'
        );

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="companies.pdf"',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $filters = $request->only(['search', 'status', 'plan_id']);
        $filters['per_page'] = 1000;
        $result = $this->companyService->list($filters);

        $rows = array_map(fn($c) => [
            $c['name'], $c['owner_name'], $c['owner_phone'], $c['plan_name'] ?? '-',
            $c['status'], $c['branches_count'], $c['employees_count'],
        ], $result['data']);

        return Excel::download(
            new GenericReportExport($rows, ['اسم الشركة', 'المالك', 'الهاتف', 'الخطة', 'الحالة', 'الفروع', 'الموظفون'], 'الشركات'),
            'companies.xlsx'
        );
    }
}