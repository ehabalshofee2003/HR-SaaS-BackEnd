<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Models\Identity\User;
use App\Services\Owner\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
    ) {}

    public function index(): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $user = User::find($userId);

        if (!$user || !$user->company_id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $data = $this->dashboardService->getDashboard($user->company_id);

        return response()->json(['success' => true, 'data' => $data]);
    }
}