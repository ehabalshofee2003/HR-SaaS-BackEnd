<?php

namespace App\Repositories\Interfaces;

interface OtpRepositoryInterface
{
    public function create(array $data): object;
    public function setCooldown(object $otp, int $seconds): void;
    public function incrementFailedAttempts(object $otp): void;
    public function markUsed(object $otp): void;
    public function invalidateOldOtps(string $phone, string $purpose): void;
    public function latestOtp(string $phone, string $purpose): ?object;
}