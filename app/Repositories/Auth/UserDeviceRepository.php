<?php

namespace App\Repositories\Auth;

use App\Repositories\Interfaces\UserDeviceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class UserDeviceRepository implements UserDeviceRepositoryInterface
{
    protected string $table = 'user_devices';

    public function isKnownDevice(int $userId, string $deviceId): bool
    {
        return DB::table($this->table)
            ->where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->exists();
    }

    public function recordDevice(int $userId, string $deviceId, ?string $deviceName): void
    {
        DB::table($this->table)->updateOrInsert(
            ['user_id' => $userId, 'device_id' => $deviceId],
            [
                'device_name' => $deviceName,
                'last_login_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}