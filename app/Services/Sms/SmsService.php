<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message): array
    {
        if (!env('SMS_ENABLED', true)) {
            Log::info('SMS MOCKED', ['phone' => $phone, 'message' => $message]);
            return ['success' => true, 'mocked' => true];
        }

        $cloudEnabled = env('SMS_CLOUD_ENABLED', false);
        $localEnabled = env('SMS_LOCAL_ENABLED', false);

        if ($cloudEnabled) {
            return $this->sendViaCloud($phone, $message);
        }

        if ($localEnabled) {
            return $this->sendViaLocal($phone, $message);
        }

        Log::error('SMS: no gateway enabled (both SMS_CLOUD_ENABLED and SMS_LOCAL_ENABLED are false)');
        return ['success' => false, 'error' => 'No SMS gateway is enabled.'];
    }

    private function sendViaCloud(string $phone, string $message): array
    {
        return $this->sendRequest(
            'https://api.sms-gate.app/3rdparty/v1/message',
            env('SMS_CLOUD_USERNAME'),
            env('SMS_CLOUD_PASSWORD'),
            $phone,
            $message
        );
    }

    private function sendViaLocal(string $phone, string $message): array
    {
        $baseUrl = rtrim(env('SMS_LOCAL_URL'), '/');

        return $this->sendRequest(
            $baseUrl . '/message',
            env('SMS_LOCAL_USERNAME'),
            env('SMS_LOCAL_PASSWORD'),
            $phone,
            $message
        );
    }

private function sendRequest(string $url, ?string $username, ?string $password, string $phone, string $message): array
{
    $attempts = 0;
    $maxAttempts = 3;
    $lastError = null;

    while ($attempts < $maxAttempts) {
        $attempts++;

        try {
            $response = Http::withBasicAuth($username, $password)
                ->timeout(30)
                ->post($url, [
                    'textMessage' => ['text' => $message],
                    'phoneNumbers' => [$this->formatPhone($phone)],
                    'withDeliveryReport' => false,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            $lastError = $response->body();
            Log::warning("SMS attempt {$attempts} failed", ['url' => $url, 'phone' => $phone, 'response' => $lastError]);
        } catch (\Throwable $e) {
            $lastError = $e->getMessage();
            Log::warning("SMS attempt {$attempts} exception", ['url' => $url, 'phone' => $phone, 'message' => $lastError]);
        }

        if ($attempts < $maxAttempts) {
            sleep(2);
        }
    }

    Log::error('SMS FAILED after retries', ['url' => $url, 'phone' => $phone, 'error' => $lastError]);
    return ['success' => false, 'error' => $lastError];
}
    private function formatPhone(string $phone): string
    {
        $phone = str_replace([' ', '-'], '', $phone);

        if (str_starts_with($phone, '0')) {
            return '+963' . substr($phone, 1);
        }

        if (str_starts_with($phone, '963')) {
            return '+' . $phone;
        }

        if (str_starts_with($phone, '9') && strlen($phone) === 9) {
            return '+963' . $phone;
        }

        return $phone;
    }
}