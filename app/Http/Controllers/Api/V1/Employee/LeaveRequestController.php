<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreLeaveRequestRequest;
use App\Http\Resources\Employee\LeaveRequestResource;
use App\Http\Resources\Employee\LeaveBalanceResource;
use App\Services\Hr\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Identity\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function __construct(private LeaveRequestService $leaveRequestService) {}

    /**
     * صفحة "قائمة الإجازات" — تُرجع الرصيد + القائمة الكاملة (قابلة للفلترة بـ status)
     */
public function index(Request $request): JsonResponse
{
    $user = User::find(Auth::id());
    if (!$user) abort(401, 'Unauthorized');

    $perPage = $request->integer('per_page', 15);
    $status = $request->query('status');

    $leaveRequests = $this->leaveRequestService->getMyLeaveRequests($user->id, $perPage, $status); // ⚠️ $user->id بدل employeeDetail->id
    $balance = $this->leaveRequestService->getCombinedBalance($user->id);

    return response()->json([
        'total_leaves' => $balance['total_days'],
        'remaining_leaves' => $balance['remaining_days'],
        'data' => LeaveRequestResource::collection($leaveRequests),
    ]);
}
    
    public function formData(): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) abort(401, 'Unauthorized');

        $balance = $this->leaveRequestService->getCombinedBalance($user->id);

        return response()->json([
            'total_leaves' => $balance['total_days'],
            'remaining_leaves' => $balance['remaining_days'],
            'leave_types' => [
                ['value' => 'annual', 'label' => 'Annual Leave'],
                ['value' => 'sick', 'label' => 'Sick Leave'],
                ['value' => 'emergency', 'label' => 'Emergency Leave'],
            ],
        ]);
    }
/**
 * الراوت القديم leave-requests/balance — أُبقي عليه لتوافقية أي شاشة تستخدمه حالياً
    */
    public function balance(): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) abort(401, 'Unauthorized');

        $balance = $this->leaveRequestService->getCombinedBalance($user->id);

        return response()->json([
            'data' => [
                'total_leaves' => $balance['total_days'],
                'remaining_leaves' => $balance['remaining_days'],
            ]
        ]);
    }
public function store(StoreLeaveRequestRequest $request): JsonResponse
{
    $user = User::find(Auth::id());
    if (!$user) abort(401, 'Unauthorized');

    try {
        $validated = $request->validated();
        $validated['company_id'] = $user->getCurrentCompanyId();

        $leaveRequest = $this->leaveRequestService->submitLeaveRequest(
            $user->id, // ⚠️ $user->id بدل employeeDetail->id
            $user->id,
            $validated,
            $request->file('attachments', [])
        );

        return response()->json([
            'message' => 'تم إرسال طلب الإجازة بنجاح.',
            'data'    => new LeaveRequestResource($leaveRequest->load('leaveType'))
        ], 201);

    } catch (\Exception $e) {
        Log::error('Leave Request Error: ' . $e->getMessage());
        return response()->json(['message' => $e->getMessage() ?: 'حدث خطأ أثناء إرسال الطلب.'], 422);
    }
}

public function show($id): LeaveRequestResource
{
    $user = User::find(Auth::id());
    if (!$user) abort(401, 'Unauthorized');

    $leaveRequest = $this->leaveRequestService->getMyLeaveRequestById($user->id, (int) $id); // ⚠️ $user->id
    if (!$leaveRequest) abort(404, 'Leave request not found.');

    return new LeaveRequestResource($leaveRequest);
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