<?php

namespace App\Repositories\Interfaces\Owner;

interface NotificationRepositoryInterface
{
    public function list(int $userId, array $filters = []): array;
    public function find(int $id, int $userId): ?object;
    public function markAsRead(int $id): void;
    public function markAllAsRead(int $userId): void;
    public function softDelete(int $id): void;
    public function unreadCount(int $userId): int;
}