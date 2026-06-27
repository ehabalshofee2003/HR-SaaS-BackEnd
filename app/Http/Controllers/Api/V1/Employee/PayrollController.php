<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\Employee\PayrollDetailResource;
use App\Http\Resources\Employee\PayrollListResource;
use App\Services\Payroll\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(private PayrollService $payrollService) {}

    public function index(Request $request): JsonResponse
    {
        $payrolls = $this->payrollService->getPayrolls();
        
        // هذا يرجع الشكل القياسي المثالي لـ Flutter (data, links, meta) بدون تكرار
        return PayrollListResource::collection($payrolls)->response();
    }

    public function show($id): JsonResponse
    {
        // استخدم (int) $id كما تم الاتفاق عليه لتجنب أخطاء الـ String
        $result = $this->payrollService->getPayrollDetail($id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json([
            'data' => new PayrollDetailResource($result['data'])
        ]);
    }

    public function pdf($id)
    {
        $result = $this->payrollService->generatePdf($id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        // إرجاع الملف كرد بصيغة PDF للـ Flutter لفتحه
        return $result['data']->stream('payslip_' . $id . '.pdf');
        
        // أو إذا تريد تحميله مباشرة استخدم:
        // return $result['data']->download('payslip_' . $id . '.pdf');
    }
}