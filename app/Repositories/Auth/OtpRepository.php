<?php

namespace App\Repositories\Auth;

use App\Repositories\Interfaces\OtpRepositoryInterface;
use Illuminate\Support\Facades\DB;

class OtpRepository implements OtpRepositoryInterface
{
    protected string $table = 'otp_requests';

    public function create(array $data): object
    {
        $id = DB::table($this->table)->insertGetId([
            'phone' => $data['phone'],
            'code_hash' => $data['code_hash'],
            'purpose' => $data['purpose'],
            'failed_attempts' => 0,
            'max_attempts' => $data['max_attempts'] ?? 5,
            'expires_at' => $data['expires_at'],
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table($this->table)->where('id', $id)->first();
    }

    public function setCooldown(object $otp, int $seconds): void
    {
        DB::table($this->table)->where('id', $otp->id)->update([
            'cooldown_until' => now()->addSeconds($seconds),
            'updated_at' => now(),
        ]);
    }

    public function incrementFailedAttempts(object $otp): void
    {
        DB::table($this->table)->where('id', $otp->id)->update([
            'failed_attempts' => $otp->failed_attempts + 1,
            'updated_at' => now(),
        ]);
    }

    public function markUsed(object $otp): void
    {
        DB::table($this->table)->where('id', $otp->id)->update([
            'used_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function invalidateOldOtps(string $phone, string $purpose): void
    {
        DB::table($this->table)
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['expires_at' => now()->subSecond(), 'updated_at' => now()]);
    }

    public function latestOtp(string $phone, string $purpose): ?object
    {
        return DB::table($this->table)
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->orderByDesc('id')
            ->first();
    }
}