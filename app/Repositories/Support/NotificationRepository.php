<?php

namespace App\Repositories\Support;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationRepository
{
    public function paginateForUser(int $userId, array $filters, int $perPage = 20)
    {
        $query = DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('deleted_at');

        if (!empty($filters['is_read'])) {
            $query->where('is_read', $filters['is_read'] === 'true' || $filters['is_read'] === '1');
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function countUnread(int $userId): int
    {
        return DB::table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->whereNull('deleted_at')
            ->count();
    }

    public function findForUser(int $id, int $userId): ?object
    {
        return DB::table('notifications')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function markRead(int $id): void
    {
        DB::table('notifications')->where('id', $id)->update([
            'is_read' => true,
            'updated_at' => Carbon::now(),
        ]);
    }

    public function markAllReadForUser(int $userId): void
    {
        DB::table('notifications')
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'updated_at' => Carbon::now()]);
    }

    /**
     * دالة عامة لإنشاء إشعار — تُستخدم من أي Epic آخر بكل المشروع
     * (تحل محل كل تعليقات // TODO: إشعار... التي تركناها بكل الـ Epics السابقة)
     */
    public function create(array $data): int
    {
        return DB::table('notifications')->insertGetId([
            'company_id' => $data['company_id'],
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'body' => $data['body'],
            'type' => $data['type'],
            'is_read' => false,
            'data' => isset($data['data']) ? json_encode($data['data']) : null,
            'link_type' => $data['link_type'] ?? null,
            'link_id' => $data['link_id'] ?? null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}