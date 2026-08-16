<?php

namespace App\Services\Auth;

use App\Models\Identity\User;
use App\Repositories\Interfaces\OtpRepositoryInterface;
use App\Repositories\Interfaces\UserDeviceRepositoryInterface;
use App\Services\Sms\SmsService;
use App\Services\Support\NotificationService;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function __construct(
        private OtpRepositoryInterface $otpRepository,
        private UserDeviceRepositoryInterface $deviceRepository,
        private SmsService $smsService,
        private NotificationService $notificationService,
    ) {}

    public function sendOtp(string $phone): array
    {
        $user = User::where('phone', $phone)->whereNull('deleted_at')->first();

        if (!$user) {
            return ['success' => false, 'code' => 404, 'message' => 'رقم الهاتف غير مسجل.'];
        }

        if ($user->status === 'suspended') {
            return ['success' => false, 'code' => 403, 'message' => 'الحساب موقوف.'];
        }

        return $this->generateAndSend($phone, 'login');
    }

    public function verifyOtp(string $phone, string $otp, ?string $deviceId = null, ?string $deviceName = null): array
    {
        $result = $this->verify($phone, $otp, 'login');

        if (!$result['success']) {
            return $result;
        }

        $user = User::where('phone', $phone)->whereNull('deleted_at')->first();

        if (!$user) {
            return ['success' => false, 'code' => 404, 'message' => 'المستخدم غير موجود.'];
        }

        if ($user->status === 'suspended') {
            return ['success' => false, 'code' => 403, 'message' => 'الحساب موقوف.'];
        }

        // تتبّع الجهاز — إشعار فقط لو جهاز جديد، من غير أي OTP إضافي
        if ($deviceId) {
            $isKnown = $this->deviceRepository->isKnownDevice($user->id, $deviceId);

if (!$isKnown) {
    $companyId = $user->getCurrentCompanyId();

    if ($companyId) {
        $this->notificationService->send([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'title' => 'New device login',
            'body' => 'Your account was logged into from a new device. If this was not you, contact your administrator immediately.',
            'type' => 'system',
        ]);
    }
}

            $this->deviceRepository->recordDevice($user->id, $deviceId, $deviceName);
        }

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

    public function sendPhoneChangeOtp(User $user, string $newPhone): array
    {
        if (User::where('phone', $newPhone)->exists()) {
            return ['success' => false, 'code' => 422, 'message' => 'رقم الهاتف الجديد مستخدم بالفعل.'];
        }

        return $this->generateAndSend($newPhone, 'change_phone');
    }

    public function verifyPhoneChangeOtp(User $user, string $newPhone, string $otp): array
    {
        $result = $this->verify($newPhone, $otp, 'change_phone');

        if (!$result['success']) {
            return $result;
        }

        if (User::where('phone', $newPhone)->exists()) {
            return ['success' => false, 'code' => 422, 'message' => 'رقم الهاتف الجديد مستخدم بالفعل.'];
        }

        $user->update(['phone' => $newPhone]);

        return [
            'success' => true,
            'code' => 200,
            'message' => 'تم تحديث رقم الهاتف بنجاح.',
        ];
    }

    private function generateAndSend(string $phone, string $purpose): array
    {
        $retryAfter = (int) env('OTP_RETRY_COOLDOWN_SECONDS', 60);
        $latestOtp = $this->otpRepository->latestOtp($phone, $purpose);

        if ($latestOtp && !$latestOtp->used_at) {
            $createdAt = \Carbon\Carbon::parse($latestOtp->created_at);
            if ($createdAt->addSeconds($retryAfter)->isFuture()) {
                return [
                    'success' => false,
                    'code' => 429,
                    'message' => 'من فضلك انتظر قبل طلب كود جديد.',
                    'retry_after' => now()->diffInSeconds($createdAt->addSeconds($retryAfter)),
                ];
            }
        }

        $otp = random_int(100000, 999999);

        $this->otpRepository->invalidateOldOtps($phone, $purpose);

        $text = $purpose === 'change_phone'
            ? "رمز تأكيد رقم الهاتف الجديد: $otp"
            : "رمز التحقق الخاص بك هو: $otp";

        $smsResult = $this->smsService->send($phone, $text);

        if (!$smsResult['success']) {
            return ['success' => false, 'code' => 422, 'message' => 'فشل إرسال الرسالة، حاول مرة أخرى.'];
        }

        $expiryMinutes = (int) env('OTP_EXPIRY_MINUTES', 5);

        $this->otpRepository->create([
            'phone' => $phone,
            'code_hash' => Hash::make($otp),
            'purpose' => $purpose,
            'max_attempts' => 5,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $response = [
            'success' => true,
            'code' => 200,
            'message' => 'تم إرسال رمز التحقق إلى ' . $this->maskPhone($phone),
            'data' => [
                'expires_in' => $expiryMinutes * 60,
                'masked_phone' => $this->maskPhone($phone),
            ],
        ];

        if (app()->environment('local') && !env('SMS_ENABLED', true)) {
            $response['data']['fake_otp'] = (string) $otp;
        }

        return $response;
    }

    private function verify(string $phone, string $otp, string $purpose): array
    {
        $otpRecord = $this->otpRepository->latestOtp($phone, $purpose);

        if (!$otpRecord) {
            return ['success' => false, 'code' => 422, 'message' => 'لم يتم العثور على رمز تحقق، اطلب رمزًا جديدًا.'];
        }

        if ($otpRecord->cooldown_until && \Carbon\Carbon::parse($otpRecord->cooldown_until)->isFuture()) {
            return [
                'success' => false,
                'code' => 429,
                'message' => 'محاولات كثيرة فاشلة، حاول لاحقًا.',
                'retry_after' => now()->diffInSeconds(\Carbon\Carbon::parse($otpRecord->cooldown_until)),
            ];
        }

        if (\Carbon\Carbon::parse($otpRecord->expires_at)->isPast()) {
            return ['success' => false, 'code' => 422, 'message' => 'انتهت صلاحية الرمز.'];
        }

        if ($otpRecord->used_at) {
            return ['success' => false, 'code' => 422, 'message' => 'تم استخدام هذا الرمز من قبل.'];
        }

        if (!Hash::check($otp, $otpRecord->code_hash)) {
            $this->otpRepository->incrementFailedAttempts($otpRecord);

            if (($otpRecord->failed_attempts + 1) >= $otpRecord->max_attempts) {
                $this->otpRepository->setCooldown($otpRecord, 900);
            }

            return [
                'success' => false,
                'code' => 422,
                'message' => 'رمز غير صحيح.',
                'remaining_attempts' => max(0, $otpRecord->max_attempts - $otpRecord->failed_attempts - 1),
            ];
        }

        $this->otpRepository->markUsed($otpRecord);

        return ['success' => true];
    }

    private function maskPhone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) return $phone;
        return substr($phone, 0, 4) . str_repeat('*', $length - 7) . substr($phone, -3);
    }
}