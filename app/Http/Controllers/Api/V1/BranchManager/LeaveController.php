<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Leave\ApproveLeaveRequest;
use App\Http\Requests\BranchManager\Leave\RejectLeaveRequest;
use App\Services\Hr\LeaveRequestService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class LeaveController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveRequestService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $leaves = $this->leaveRequestService->list($user, $request->only(['status', 'type', 'employee_id', 'from', 'to']));

        return response()->json(['data' => $leaves]);
    }

    public function show(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $leave = $this->leaveRequestService->getDetails((int) $id, $user);

        return response()->json(['data' => $leave]);
    }

    public function approve(ApproveLeaveRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $leave = $this->leaveRequestService->approve((int) $id, $user, $request->validated()['note'] ?? null);

        return response()->json(['data' => $leave]);
    }

    public function reject(RejectLeaveRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $leave = $this->leaveRequestService->reject((int) $id, $user, $request->validated()['rejection_reason']);

        return response()->json(['data' => $leave]);
    }

    public function balances(Request $request, $employee_user_id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $balances = $this->leaveRequestService->getEmployeeBalances((int) $employee_user_id, $user);

        return response()->json(['data' => $balances]);
    }
}