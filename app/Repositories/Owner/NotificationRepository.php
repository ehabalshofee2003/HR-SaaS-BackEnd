<?php

namespace App\Repositories\Owner;

use App\Repositories\Interfaces\Owner\NotificationRepositoryInterface;
use Illuminate\Support\Facades\DB;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function list(int $userId, array $filters = []): array
    {
        $query = DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('deleted_at');

        if (isset($filters['is_read'])) {
            $query->where('is_read', (bool) $filters['is_read']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);
        $page = (int) ($filters['page'] ?? 1);

        $total = (clone $query)->count();

        $items = $query->orderByDesc('created_at')
            ->forPage($page, $perPage)
            ->get(['id', 'title', 'body', 'type', 'is_read', 'data', 'created_at'])
            ->all();

        return [
            'data' => $items,
            'meta' => [
                'total' => $total,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
                'per_page' => $perPage,
            ],
        ];
    }

    public function find(int $id, int $userId): ?object
    {
        return DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function markAsRead(int $id): void
    {
        DB::table('notifications')->where('id', $id)->update([
            'is_read' => true,
            'updated_at' => now(),
        ]);
    }

    public function markAllAsRead(int $userId): void
    {
        DB::table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->whereNull('deleted_at')
            ->update([
                'is_read' => true,
                'updated_at' => now(),
            ]);
    }

    public function softDelete(int $id): void
    {
        DB::table('notifications')->where('id', $id)->update([
            'deleted_at' => now(),
        ]);
    }

    public function unreadCount(int $userId): int
    {
        return DB::table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->whereNull('deleted_at')
            ->count();
    }
}