<?php

namespace App\Repositories\Interfaces\Supervisor;

interface ProfileRepositoryInterface
{
    public function getProfile(int $userId): ?object;
    public function updateAvatar(int $userId, string $avatarPath): void;
}