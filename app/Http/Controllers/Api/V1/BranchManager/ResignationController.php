<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Resignation\RejectResignationRequest;
use App\Services\Hr\ResignationService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class ResignationController extends Controller
{
    public function __construct(
        protected ResignationService $resignationService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $resignations = $this->resignationService->list($user, $request->only(['status']));

        return response()->json(['data' => $resignations]);
    }

    public function show(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $resignation = $this->resignationService->getDetails((int) $id, $user);

        return response()->json(['data' => $resignation]);
    }

    public function accept(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $resignation = $this->resignationService->accept((int) $id, $user);

        return response()->json(['data' => $resignation]);
    }

    public function reject(RejectResignationRequest $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $resignation = $this->resignationService->reject((int) $id, $user, $request->validated()['reason']);

        return response()->json(['data' => $resignation]);
    }
}