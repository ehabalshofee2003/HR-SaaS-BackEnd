<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\Employee\NotificationResource;
use App\Services\Support\NotificationService;
use App\Models\Identity\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $service) {}

    public function index(): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $notifications = $this->service->getAll($user);
        
        return response()->json([
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
            ]
        ]);
    }

    public function markRead($id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $notification = $this->service->markAsRead($user, $id);
        
        if (!$notification) {
            return response()->json(['message' => 'Notification not found.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Marked as read successfully.',
            'data'    => new NotificationResource($notification)
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);

        $this->service->markAllAsRead($user);

        return response()->json(['message' => 'All notifications marked as read successfully.']);
    }
}