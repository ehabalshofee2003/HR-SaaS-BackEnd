<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreLeaveRequestRequest;
use App\Http\Resources\Employee\LeaveRequestResource;
use App\Services\Hr\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use App\Models\Identity\User;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\Employee\LeaveBalanceResource;

class LeaveRequestController extends Controller
{
    public function __construct(private LeaveRequestService $leaveRequestService) {}

    public function index(): AnonymousResourceCollection
    {
        // Auth Type Hinting لتجنب أخطاء Intelephense
        $user = User::find(Auth::id());
        if (!$user) {
            abort(401, 'Unauthorized');
        }

        $perPage = request()->integer('per_page', 15);
        $leaveRequests = $this->leaveRequestService->getMyLeaveRequests($user->employeeDetail->id, $perPage);

        return LeaveRequestResource::collection($leaveRequests);
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) {
            abort(401, 'Unauthorized');
        }

        try {
            $validated = $request->validated();
            
            // إضافة company_id للبيانات
            // ملاحظة معمارية: في المستقبل ستأخذها من $user->employeeDetail->company_id
            // حالياً نضعها 1 للاختبار لأننا في بيئة شركة واحدة
            $validated['company_id'] = 1; 

            $leaveRequest = $this->leaveRequestService->submitLeaveRequest(
                $user->employeeDetail->id,
                $validated,
                $request->file('attachment')
            );

            return response()->json([
                'message' => 'Leave request submitted successfully.',
                'data'    => new LeaveRequestResource($leaveRequest->load('leaveType'))
            ], 201);

        } catch (\Exception $e) {
            Log::error('Leave Request Error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'An error occurred while submitting your request.'
            ], 500);
        }
    }

    public function show(int $id): LeaveRequestResource
    {
        $user = User::find(Auth::id());
        if (!$user) {
            abort(401, 'Unauthorized');
        }

        $leaveRequest = $this->leaveRequestService->getMyLeaveRequestById($user->employeeDetail->id, $id);
        if (!$leaveRequest) {
            abort(404, 'Leave request not found.');
        }

        return new LeaveRequestResource($leaveRequest);
    }
        public function balance(): JsonResponse
    {
        $result = $this->leaveRequestService->getBalance();
        return response()->json([
            'data' => LeaveBalanceResource::collection($result['data'])
        ]);
    }
    public function cancel($id): JsonResponse
    {
        $result = $this->leaveRequestService->cancelRequest($id);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json(['message' => $result['message']]);
    }
}