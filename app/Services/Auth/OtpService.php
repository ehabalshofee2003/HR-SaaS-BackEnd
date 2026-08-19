<?php

namespace App\Services\Auth;

use App\Models\Identity\User;
use App\Services\Sms\SmsService;

class OtpService
{
    public function __construct(
        private SmsService $smsService,
    ) {}

    /**
     * الدالة الأولى: توليد كود 6 أرقام، إرساله عبر API الشركة،
     * وحفظه مباشرة في عمود otp_message على صف المستخدم نفسه.
     */
public function sendOtp(string $contacts, string $smsType, ?string $customMessage = null): array
{
    $phone = $this->normalizePhone($contacts);

    $user = User::where('phone', $phone)->whereNull('deleted_at')->first();

    if (!$user) {
        return ['success' => false, 'code' => 404, 'message' => 'رقم الهاتف غير مسجل.'];
    }

    if ($user->status === 'suspended') {
        return ['success' => false, 'code' => 403, 'message' => 'الحساب موقوف.'];
    }

    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $text = ($customMessage ? $customMessage . ' ' : '') . "يا حبيبي هاد ال: $otp";

    $smsResult = $this->smsService->send($phone, $text);

    if (!$smsResult['success']) {
        return [
            'success' => false,
            'code' => 422,
            'message' => 'فشل إرسال الرسالة.',
            'sms_provider_response' => $smsResult,
        ];
    }

    $expiryMinutes = (int) env('OTP_EXPIRY_MINUTES', 5);

    $user->update([
        'otp_message' => $otp,
        'otp_expires_at' => now()->addMinutes($expiryMinutes),
    ]);

    return ['success' => true, 'code' => 200, 'message' => 'تم إرسال رمز التحقق.'];
}

private function normalizePhone(string $contacts): string
{
    $phone = preg_replace('/\D+/', '', $contacts);
    if (str_starts_with($phone, '963')) {
        return '0' . substr($phone, 3);
    }
    return $phone;
}

    /**
     * الدالة الثانية: مقارنة الكود المُدخل بالكود المخزّن في otp_message
     * الخاص بنفس المستخدم، والتأكد من عدم انتهاء الصلاحية.
     */
    public function verifyOtp(string $phone, string $otp): array
    {
        $user = User::where('phone', $phone)->whereNull('deleted_at')->first();

        if (!$user) {
            return ['success' => false, 'code' => 404, 'message' => 'المستخدم غير موجود.'];
        }

        if (!$user->otp_message) {
            return ['success' => false, 'code' => 422, 'message' => 'لم يتم إرسال رمز تحقق بعد.'];
        }

        if ($user->otp_expires_at && now()->greaterThan($user->otp_expires_at)) {
            return ['success' => false, 'code' => 422, 'message' => 'انتهت صلاحية الرمز.'];
        }

        if ($otp !== $user->otp_message) {
            return ['success' => false, 'code' => 422, 'message' => 'رمز غير صحيح.'];
        }

        // الكود صح — نمسحه فورًا عشان ميتعادش استخدامه تاني
        $user->update([
            'otp_message' => null,
            'otp_expires_at' => null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->update(['last_login_at' => now()]);
        $user->load('profile');

        return [
            'success' => true,
            'code' => 200,
            'token' => $token,
            'user' => $user,
        ];
    }
}