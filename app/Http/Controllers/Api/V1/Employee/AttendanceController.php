<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\CheckQrCodeRequest;
use App\Services\Hr\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
    ) {}

    public function checkIn(CheckQrCodeRequest $request): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $result = $this->attendanceService->checkIn($userId, $request->validated()['qr_code']);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], $result['code']);
        }

        return response()->json(['success' => true, 'message' => 'Checked in successfully.', 'data' => $result['data']]);
    }

    public function checkOut(CheckQrCodeRequest $request): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $result = $this->attendanceService->checkOut($userId, $request->validated()['qr_code']);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], $result['code']);
        }

        return response()->json(['success' => true, 'message' => 'Checked out successfully.', 'data' => $result['data']]);
    }
}