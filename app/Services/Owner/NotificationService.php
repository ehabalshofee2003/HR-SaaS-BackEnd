<?php

namespace App\Services\Owner;

use App\Repositories\Interfaces\Owner\NotificationRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
    ) {}

    public function list(int $userId, array $filters = []): array
    {
        $result = $this->repository->list($userId, $filters);

        return [
            'data' => array_map(fn($n) => $this->format($n), $result['data']),
            'meta' => array_merge($result['meta'], [
                'unread_count' => $this->repository->unreadCount($userId),
            ]),
        ];
    }

    public function markAsRead(int $id, int $userId): array
    {
        $notification = $this->repository->find($id, $userId);

        if (!$notification) {
            throw ValidationException::withMessages(['notification' => ['Notification not found.']]);
        }

        $this->repository->markAsRead($id);

        return ['success' => true, 'message' => 'Notification marked as read.'];
    }

    public function markAllAsRead(int $userId): array
    {
        $this->repository->markAllAsRead($userId);

        return ['success' => true, 'message' => 'All notifications marked as read.'];
    }

    public function delete(int $id, int $userId): array
    {
        $notification = $this->repository->find($id, $userId);

        if (!$notification) {
            throw ValidationException::withMessages(['notification' => ['Notification not found.']]);
        }

        $this->repository->softDelete($id);

        return ['success' => true, 'message' => 'Notification deleted.'];
    }

    private function format(object $n): array
    {
        return [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'type' => $n->type,
            'is_read' => (bool) $n->is_read,
            'data' => $n->data ? json_decode($n->data, true) : null,
            'created_at' => Carbon::parse($n->created_at)->toIso8601String(),
        ];
    }
}