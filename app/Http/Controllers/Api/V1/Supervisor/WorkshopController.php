<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Identity\User;
use App\Services\Hr\WorkshopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class WorkshopController extends Controller
{
    public function __construct(protected WorkshopService $workshopService) {}

    private function currentUser(): ?User
    {
        $id = Auth::id();
        return $id ? User::find($id) : null;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        $result = $this->workshopService->list($user, $request->only(['status']));
        return response()->json(['success' => true, 'data' => $result]);
    }

    public function register(int $id): JsonResponse
    {
        $user = $this->currentUser();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        try {
            $branchId = $user->getCurrentBranchId();
            $workshop = $this->workshopService->getDetails($id, $user);

            if ($workshop->status !== 'upcoming') {
                return response()->json(['success' => false, 'message' => 'Workshop cannot be registered.'], 422);
            }

            $this->workshopService->register($user, $id);
            return response()->json(['success' => true, 'message' => 'Registered successfully.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        $user = $this->currentUser();
        if (!$user) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        try {
            $result = $this->workshopService->unregister($user, $id);
            if (is_array($result) && isset($result['error'])) {
                return response()->json(['success' => false, 'message' => $result['error']], $result['code']);
            }
            return response()->json(['success' => true, 'message' => 'Registration cancelled.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}