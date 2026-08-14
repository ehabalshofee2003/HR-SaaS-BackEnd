<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ExceptionDecideRequest;
use App\Models\Identity\User;
use App\Services\Owner\ExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExceptionController extends Controller
{
    public function __construct(
        private ExceptionService $exceptionService,
    ) {}

    private function currentUser(): ?User
    {
        $userId = Auth::id();

        if (!$userId) {
            return null;
        }

        return User::find($userId);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        if (!$user || !$user->company_id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $filters = $request->only(['status', 'type', 'branch_id', 'from', 'to', 'page', 'per_page']);
        $result = $this->exceptionService->list($user->company_id, $filters);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->currentUser();

        if (!$user || !$user->company_id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $exception = $this->exceptionService->get($id, $user->company_id);

        return response()->json(['success' => true, 'data' => $exception]);
    }

    public function decide(ExceptionDecideRequest $request, int $id): JsonResponse
    {
        $user = $this->currentUser();

        if (!$user || !$user->company_id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $result = $this->exceptionService->decide($id, $user->company_id, $user->id, $request->validated());

        return response()->json($result);
    }

    public function pendingCount(): JsonResponse
    {
        $user = $this->currentUser();

        if (!$user || !$user->company_id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $result = $this->exceptionService->pendingCount($user->company_id);

        return response()->json(['success' => true, 'data' => $result]);
    }
}