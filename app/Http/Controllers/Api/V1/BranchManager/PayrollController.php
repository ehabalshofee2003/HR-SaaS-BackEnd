<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Payroll\CalculatePayrollRequest;
use App\Http\Requests\BranchManager\Payroll\UpdatePayrollEntryRequest;
use App\Http\Requests\BranchManager\Payroll\AddPayrollExceptionRequest;
use App\Services\Payroll\PayrollService;
use App\Exports\PayrollExport;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollService $payrollService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $payrolls = $this->payrollService->list($user, $request->only(['year', 'status']));

        return response()->json(['data' => $payrolls]);
    }

    public function calculate(CalculatePayrollRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $result = $this->payrollService->calculate($user, $request->validated()['month'], $request->validated()['year']);

        return response()->json(['data' => $result], 201);
    }

    public function show(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $result = $this->payrollService->getDetails((int) $id, $user);

        return response()->json(['data' => $result]);
    }

    public function updateEntry(UpdatePayrollEntryRequest $request, $id, $employee_user_id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $record = $this->payrollService->updateEntry((int) $id, (int) $employee_user_id, $request->validated(), $user);

        return response()->json(['data' => $record]);
    }

    public function addException(AddPayrollExceptionRequest $request, $id, $employee_user_id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $record = $this->payrollService->addException((int) $id, (int) $employee_user_id, $request->validated(), $user);

        return response()->json(['data' => $record], 201);
    }

    public function approve(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $period = $this->payrollService->approve((int) $id, $user);

        return response()->json(['data' => $period]);
    }

    public function markAsPaid(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $period = $this->payrollService->markAsPaid((int) $id, $user);

        return response()->json(['data' => $period]);
    }

    public function exportPdf(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $result = $this->payrollService->getExportData((int) $id, $user);

        $pdf = Pdf::loadView('exports.payroll', $result);
        return $pdf->download('payroll_' . $result['period']->month . '_' . $result['period']->year . '.pdf');
    }

    public function exportExcel(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $result = $this->payrollService->getExportData((int) $id, $user);

        return Excel::download(new PayrollExport($result['records']), 'payroll_' . $result['period']->month . '_' . $result['period']->year . '.xlsx');
    }
}