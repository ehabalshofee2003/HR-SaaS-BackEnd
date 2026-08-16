<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\RejectLeaveRequest;
use App\Services\Supervisor\LeaveRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Exception;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveRequestService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $supervisorId = Auth::id();
        $filters = $request->only(['status', 'employee_id']);
        $perPage = $request->integer('per_page', 15);

        $leaveRequests = $this->leaveRequestService->list($supervisorId, $filters, $perPage);

        return response()->json(['success' => true, 'data' => $leaveRequests]);
    }

    public function show(int $id): JsonResponse
    {
        $supervisorId = Auth::id();

        try {
            $result = $this->leaveRequestService->getDetails($id, $supervisorId);
            return response()->json(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $supervisorId = Auth::id();

        try {
            $leaveRequest = $this->leaveRequestService->approve($id, $supervisorId, $request->input('notes'));
            return response()->json(['success' => true, 'message' => 'تم تحديث حالة الطلب بنجاح.', 'data' => $leaveRequest]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function reject(RejectLeaveRequest $request, int $id): JsonResponse
    {
        $supervisorId = Auth::id();

        try {
            $leaveRequest = $this->leaveRequestService->reject($id, $supervisorId, $request->validated()['reason']);
            return response()->json(['success' => true, 'message' => 'تم رفض الطلب.', 'data' => $leaveRequest]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }
}