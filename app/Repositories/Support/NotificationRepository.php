<?php

namespace App\Repositories\Support;

use App\Models\Support\Notification;

class NotificationRepository
{
    public function getUserNotifications(int $userId, int $companyId)
    {
        return Notification::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(15);
    }

    public function findUserNotification(int $id, int $userId, int $companyId): ?Notification
    {
        return Notification::where('id', $id)
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->first();
    }

    public function markAsRead(Notification $notification): bool
    {
        if ($notification->is_read) {
            return true; // لا داعي لتحديث الداتا بيز إذا كان مقروءاً مسبقاً
        }
        return $notification->update(['is_read' => true]);
    }

    public function markAllAsRead(int $userId, int $companyId): bool
    {
        // تحديث كل الإشعارات غير المقروءة دفعة واحدة
        return Notification::where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('is_read', false)
            ->update(['is_read' => true]) > 0;
    }
}