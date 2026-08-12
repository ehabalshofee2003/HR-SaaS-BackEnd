<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Services\Support\NotificationService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function index(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $notifications = $this->notificationService->list($user, $request->only(['is_read', 'type']));

        return response()->json(['data' => $notifications]);
    }

    public function markRead(Request $request, $id)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $this->notificationService->markRead((int) $id, $user);

        return response()->json(['message' => 'تم تحديد الإشعار كمقروء.']);
    }

    public function markAllRead(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $this->notificationService->markAllRead($user);

        return response()->json(['message' => 'تم تحديد كل الإشعارات كمقروءة.']);
    }
}