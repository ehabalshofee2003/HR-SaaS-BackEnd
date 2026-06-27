<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\Employee\DashboardResource;
use App\Services\Employee\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(): JsonResponse
    {
        $user = \App\Models\Identity\User::find(Auth::id());

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $data = $this->dashboardService->getDashboard($user->id);

        return response()->json([
            'data' => new DashboardResource($data),
        ]);
    }
}