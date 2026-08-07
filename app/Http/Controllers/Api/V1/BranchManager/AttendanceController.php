<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Attendance\ManualAttendanceRequest;
use App\Http\Requests\BranchManager\Attendance\UpdateAttendanceRequest;
use App\Services\Hr\AttendanceService;
use App\Exports\AttendanceExport;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $records = $this->attendanceService->list($user, $request->only(['date', 'department_id', 'status', 'employee_id']));

        return response()->json(['data' => $records]);
    }

    public function storeManual(ManualAttendanceRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $record = $this->attendanceService->createManual($user, $request->validated());

        return response()->json(['data' => $record], 201);
    }

    public function update(UpdateAttendanceRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $record = $this->attendanceService->update((int) $id, $request->validated(), $user);

        return response()->json(['data' => $record]);
    }

    public function export(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $filters = $request->only(['date', 'department_id', 'status']);
        $rows = $this->attendanceService->getExportData($user, $filters);

        $format = $request->query('format', 'excel');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.attendance', ['rows' => $rows]);
            return $pdf->download('attendance_' . now()->format('Y_m_d') . '.pdf');
        }

        $exportRows = array_map(function ($row) {
            return [
                'date' => \Carbon\Carbon::parse($row->check_in)->format('Y-m-d'),
                'employee' => $row->employee_name,
                'department' => $row->department_name,
                'check_in' => \Carbon\Carbon::parse($row->check_in)->format('H:i'),
                'check_out' => $row->check_out ? \Carbon\Carbon::parse($row->check_out)->format('H:i') : '-',
                'work_hours' => $row->work_hours,
                'status' => $row->status,
            ];
        }, $rows);

        return Excel::download(new AttendanceExport($exportRows), 'attendance_' . now()->format('Y_m_d') . '.xlsx');
    }
}