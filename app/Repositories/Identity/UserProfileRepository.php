<?php

namespace App\Repositories\Identity;

use App\Models\Identity\User;
use App\Models\Identity\UserProfile;

class UserProfileRepository
{
    /**
     * جلب المستخدم مع العلاقات المطلوبة
     */
    public function getUserWithProfile(int $userId): User
    {
        // نستخدم with() لعمل Eager Loading وتجنب N+1
        return User::with(['profile', 'employeeDetail'])->findOrFail($userId);
    }

    /**
     * تحديث أو إنشاء الملف الشخصي
     */
    public function updateOrCreateProfile(User $user, array $data): UserProfile
    {
        return $user->profile()->updateOrCreate(
            ['user_id' => $user->id], // شرط البحث
            $data                     // البيانات للتحديث
        );
    }
}