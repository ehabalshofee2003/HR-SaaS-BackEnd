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

public function sendOtp(Request $request): JsonResponse
{
    $request->validate([
        'contacts' => ['required', 'string'],
        'sms_type' => ['required', 'string'],
        'message' => ['nullable', 'string'],
    ]);

    $result = $this->otpService->sendOtp(
        $request->query('contacts'),
        $request->query('sms_type'),
        $request->query('message')
    );

    return response()->json($result, $result['code'] ?? ($result['success'] ? 200 : 422));
}

public function verifyOtp(Request $request): JsonResponse
{
    $request->validate([
        'phone' => ['required', 'string'],
        'otp' => ['required', 'string'],
    ]);

    $result = $this->otpService->verifyOtp(
        $request->input('phone'),
        $request->input('otp')
    );

    return response()->json($result, $result['code'] ?? ($result['success'] ? 200 : 422));
}


 

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح']);
    }
}