<?php

namespace App\Repositories\Interfaces\Owner;

interface ProfileRepositoryInterface
{
    public function getProfile(int $userId): ?object;
    public function updateProfile(int $userId, array $userData, array $profileData): void;
}