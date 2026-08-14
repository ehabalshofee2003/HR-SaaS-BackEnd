<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\NotificationFilterRequest;
use App\Services\Owner\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public function index(NotificationFilterRequest $request): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $result = $this->notificationService->list($userId, $request->validated());

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    public function markAsRead(int $id): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $result = $this->notificationService->markAsRead($id, $userId);

        return response()->json($result);
    }

    public function markAllAsRead(): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $result = $this->notificationService->markAllAsRead($userId);

        return response()->json($result);
    }

    public function destroy(int $id): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $result = $this->notificationService->delete($id, $userId);

        return response()->json($result);
    }
}