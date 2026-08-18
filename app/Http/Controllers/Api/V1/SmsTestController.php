<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SmsTestController extends Controller
{
    /**
     * Endpoint تيست بس — بنفس شكل طلب الشركة تمامًا (GET، contacts/sms_type/message).
     * ⚠️ منفصل تمامًا عن /auth/send-otp، مالوش أي علاقة بنظام الدخول.
     */
    public function testSend(Request $request): JsonResponse
    {
        $request->validate([
            'contacts' => ['required', 'string'],
            'sms_type' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        $response = Http::timeout(30)->get('https://sms.asmm.live/api/sms/send', [
            'contacts' => $request->query('contacts'),
            'sms_type' => $request->query('sms_type'),
            'message' => $request->query('message'),
        ]);

        return response()->json([
            'success' => $response->successful(),
            'sms_provider_response' => $response->json(),
        ], $response->status());
    }
}