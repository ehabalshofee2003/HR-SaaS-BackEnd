<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestPhoneChangeRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\VerifyPhoneChangeRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private OtpService $otpService,
    ) {}

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $result = $this->otpService->sendOtp($request->validated()['phone']);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
            'retry_after' => $result['retry_after'] ?? null,
        ], $result['code']);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->otpService->verifyOtp(
            $data['phone'],
            $data['otp'],
            $data['device_id'] ?? null,
            $data['device_name'] ?? null,
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'retry_after' => $result['retry_after'] ?? null,
                'remaining_attempts' => $result['remaining_attempts'] ?? null,
            ], $result['code']);
        }

        return response()->json(new LoginResource($result));
    }
public function requestPhoneChange(RequestPhoneChangeRequest $request): JsonResponse
{
    $userId = \Illuminate\Support\Facades\Auth::id();

    if (!$userId) {
        return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
    }

    $user = \App\Models\Identity\User::find($userId);

    if (!$user) {
        return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
    }

    $result = $this->otpService->sendPhoneChangeOtp($user, $request->validated()['new_phone']);

    return response()->json([
        'success' => $result['success'],
        'message' => $result['message'],
        'data' => $result['data'] ?? null,
        'retry_after' => $result['retry_after'] ?? null,
    ], $result['code']);
}

public function verifyPhoneChange(VerifyPhoneChangeRequest $request): JsonResponse
{
    $userId = \Illuminate\Support\Facades\Auth::id();

    if (!$userId) {
        return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
    }

    $user = \App\Models\Identity\User::find($userId);

    if (!$user) {
        return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
    }

    $data = $request->validated();
    $result = $this->otpService->verifyPhoneChangeOtp($user, $data['new_phone'], $data['otp']);

    return response()->json([
        'success' => $result['success'],
        'message' => $result['message'],
    ], $result['code']);
}

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح']);
    }
}