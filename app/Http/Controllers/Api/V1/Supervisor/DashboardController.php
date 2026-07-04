<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Services\Hr\SupervisorDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Identity\User;

class DashboardController extends Controller
{
    public function __construct(
        private SupervisorDashboardService $dashboardService
    ) {}

    public function index(): JsonResponse
    {
        // القيد الصارم: التحقق من المستخدم بهذه الطريقة فقط
        $user = User::find(Auth::id());
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $data = $this->dashboardService->getDashboardData($user);

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}