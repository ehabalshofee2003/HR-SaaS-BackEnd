<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Complaint\ReplyComplaintRequest;
use App\Http\Requests\BranchManager\Complaint\EscalateComplaintRequest;
use App\Http\Requests\BranchManager\Complaint\RejectComplaintRequest;
use App\Services\Hr\ComplaintService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $complaints = $this->complaintService->list($user, $request->only(['status']));

        return response()->json(['data' => $complaints]);
    }

    public function show(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $complaint = $this->complaintService->getDetails((int) $id, $user);

        return response()->json(['data' => $complaint]);
    }

    public function reply(ReplyComplaintRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $complaint = $this->complaintService->reply((int) $id, $user, $request->validated()['message']);

        return response()->json(['data' => $complaint], 201);
    }

    public function escalate(EscalateComplaintRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $complaint = $this->complaintService->escalate((int) $id, $user, $request->validated()['note']);

        return response()->json(['data' => $complaint]);
    }

    public function resolve(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $complaint = $this->complaintService->resolve((int) $id, $user, $request->input('response'));

        return response()->json(['data' => $complaint]);
    }

    public function reject(RejectComplaintRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $complaint = $this->complaintService->reject((int) $id, $user, $request->validated()['reason']);

        return response()->json(['data' => $complaint]);
    }
}