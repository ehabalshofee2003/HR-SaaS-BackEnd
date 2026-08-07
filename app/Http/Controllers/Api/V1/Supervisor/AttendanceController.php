<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\ManualAttendanceRequest;
use App\Services\Hr\SupervisorAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Identity\User;

class AttendanceController extends Controller
{
    public function __construct(private SupervisorAttendanceService $service) {}

    public function manualRecord(ManualAttendanceRequest $request): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $this->service->recordManualAttendance($user, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance recorded successfully.'
        ], 201);
    }

    public function index(): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        return response()->json([
            'status' => 'success',
            'data' => $this->service->getTeamAttendance($user, request()->query())
        ]);
    }

    public function update(int $id): JsonResponse
    {
        $user = User::find(Auth::id());
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $this->service->updateAttendanceLog($user, $id, request()->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance log updated successfully.'
        ]);
    }
}