<?php

namespace App\Services\Support;

use App\Repositories\Support\NotificationRepository;

class NotificationService
{
    public function __construct(
        private NotificationRepository $repository
    ) {}

    public function getAll($user)
    {
        $companyId = $user->getCurrentCompanyId();
        return $this->repository->getUserNotifications($user->id, $companyId);
    }

    public function markAsRead($user, $id)
    {
        $companyId = $user->getCurrentCompanyId();
        $notification = $this->repository->findUserNotification((int)$id, $user->id, $companyId);

        if (!$notification) {
            return null;
        }

        $this->repository->markAsRead($notification);
        return $notification->fresh();
    }

    public function markAllAsRead($user)
    {
        $companyId = $user->getCurrentCompanyId();
        return $this->repository->markAllAsRead($user->id, $companyId);
    }
}