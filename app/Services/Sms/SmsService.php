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

        try {
            $url = 'https://sms.asmm.live/api/sms/send';

            $response = Http::timeout(30)->get($url, [
                'contacts' => $this->formatPhone($phone),
                'sms_type' => 'plain',
                'message' => $message,
            ]);

            if ($response->successful() && $response->json('success') === true) {
                return ['success' => true, 'data' => $response->json()];
            }

            Log::error('SMS FAILED', ['phone' => $phone, 'response' => $response->body()]);
            return ['success' => false, 'error' => $response->body()];
        } catch (\Throwable $e) {
            Log::error('SMS EXCEPTION', ['message' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function formatPhone(string $phone): string
    {
        $phone = str_replace([' ', '-'], '', $phone);

        if (str_starts_with($phone, '0')) {
            return '963' . substr($phone, 1);
        }

        if (str_starts_with($phone, '+963')) {
            return substr($phone, 1);
        }

        if (str_starts_with($phone, '963')) {
            return $phone;
        }

        return $phone;
    }
}