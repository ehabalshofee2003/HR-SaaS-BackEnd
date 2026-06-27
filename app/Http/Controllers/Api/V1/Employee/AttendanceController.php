<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\Employee\AttendanceHistoryResource;
use App\Http\Resources\Employee\AttendanceTodayResource;
use App\Services\Hr\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Employee\CheckInRequest;
use App\Http\Resources\Employee\CheckInResource;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    // موجودة مسبقاً
    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $user = \App\Models\Identity\User::find(Auth::id());

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $result = $this->attendanceService->checkIn($user->id, $request->qr_code);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json([
            'message' => 'Check-in recorded successfully',
            'data'    => new CheckInResource($result['log']),
        ]);
    }
    // === الدوال الجديدة ===

    public function today(): JsonResponse
    {
        $result = $this->attendanceService->getTodayStatus();
        return response()->json([
            'data' => $result['data'] ? new AttendanceTodayResource($result['data']) : null
        ]);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $result = $this->attendanceService->checkOut($request->notes);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => new AttendanceTodayResource($result['data'])
        ]);
    }

    public function history(): JsonResponse
    {
        $logs = $this->attendanceService->getHistory();
        return AttendanceHistoryResource::collection($logs)->response();
    }
}