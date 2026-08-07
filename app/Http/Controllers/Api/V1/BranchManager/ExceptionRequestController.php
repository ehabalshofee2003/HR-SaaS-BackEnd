<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\ExceptionRequest\ForwardToOwnerRequest;
use App\Http\Requests\BranchManager\ExceptionRequest\RejectExceptionRequest;
use App\Services\Hr\ExceptionRequestService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class ExceptionRequestController extends Controller
{
    public function __construct(
        protected ExceptionRequestService $exceptionRequestService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $requests = $this->exceptionRequestService->list($user, $request->only(['employee_id', 'type']));

        return response()->json(['data' => $requests]);
    }

    public function show(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $exceptionRequest = $this->exceptionRequestService->getDetails((int) $id, $user);

        return response()->json(['data' => $exceptionRequest]);
    }

    public function forwardToOwner(ForwardToOwnerRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $exceptionRequest = $this->exceptionRequestService->forwardToOwner((int) $id, $user, $request->validated()['note']);

        return response()->json(['data' => $exceptionRequest]);
    }

    public function reject(RejectExceptionRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) {
            throw new Exception('غير مصرح.', 401);
        }

        $exceptionRequest = $this->exceptionRequestService->reject((int) $id, $user, $request->validated()['rejection_reason']);

        return response()->json(['data' => $exceptionRequest]);
    }
}