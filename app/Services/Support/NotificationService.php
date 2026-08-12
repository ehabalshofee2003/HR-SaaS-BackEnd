<?php

namespace App\Services\Support;

use App\Repositories\Support\NotificationRepository;
use App\Models\Identity\User;
use Exception;

class NotificationService
{
    public function __construct(
        protected NotificationRepository $notificationRepository
    ) {}

    public function list(User $user, array $filters)
    {
        return $this->notificationRepository->paginateForUser($user->id, $filters);
    }

    public function markRead(int $id, User $user): void
    {
        $notification = $this->notificationRepository->findForUser($id, $user->id);

        if (!$notification) {
            throw new Exception('الإشعار غير موجود.', 404);
        }

        $this->notificationRepository->markRead($id);
    }

    public function markAllRead(User $user): void
    {
        $this->notificationRepository->markAllReadForUser($user->id);
    }

    /**
     * نقطة الدخول الموحّدة لإرسال إشعار من أي Epic بالمشروع.
     * مثال استخدام من أي Service آخر:
     * app(NotificationService::class)->send([
     *     'company_id' => $companyId, 'user_id' => $employeeId,
     *     'title' => 'تمت الموافقة على إجازتك', 'body' => '...',
     *     'type' => 'leave', 'link_type' => 'leave_request', 'link_id' => $leaveId,
     * ]);
     */
    public function send(array $data): int
    {
        return $this->notificationRepository->create($data);
    }
}