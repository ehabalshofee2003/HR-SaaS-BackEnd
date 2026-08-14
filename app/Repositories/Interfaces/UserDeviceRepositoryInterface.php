<?php

namespace App\Repositories\Interfaces;

interface UserDeviceRepositoryInterface
{
    public function isKnownDevice(int $userId, string $deviceId): bool;
    public function recordDevice(int $userId, string $deviceId, ?string $deviceName): void;
}