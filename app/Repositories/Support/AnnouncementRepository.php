<?php

namespace App\Repositories\Support;

use App\Models\Support\Announcement;
use App\Models\Support\AnnouncementRead;
use Carbon\Carbon;

class AnnouncementRepository
{
    public function getEmployeeAnnouncements(int $companyId, int $userId, ?int $branchId, ?int $departmentId, int $perPage = 15)
    {
        return Announcement::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('start_date', '<=', Carbon::today()->toDateString())
            ->where('end_date', '>=', Carbon::today()->toDateString())
            ->where(function ($query) use ($userId, $branchId, $departmentId) {
                $query->where('target_type', 'all')
                      ->when($branchId, fn($q) => $q->orWhere('target_type', 'branch')->where('target_id', $branchId))
                      ->when($departmentId, fn($q) => $q->orWhere('target_type', 'department')->where('target_id', $departmentId))
                      ->orWhere('target_type', 'employee')->where('target_id', $userId);
            })
            ->with(['reads' => fn($q) => $q->where('user_id', $userId)])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findAnnouncementForEmployee(int $id, int $companyId, int $userId, ?int $branchId, ?int $departmentId): ?Announcement
    {
        return Announcement::where('id', $id)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('start_date', '<=', Carbon::today()->toDateString())
            ->where('end_date', '>=', Carbon::today()->toDateString())
            ->where(function ($query) use ($userId, $branchId, $departmentId) {
                $query->where('target_type', 'all')
                      ->when($branchId, fn($q) => $q->orWhere('target_type', 'branch')->where('target_id', $branchId))
                      ->when($departmentId, fn($q) => $q->orWhere('target_type', 'department')->where('target_id', $departmentId))
                      ->orWhere('target_type', 'employee')->where('target_id', $userId);
            })
            ->with(['reads' => fn($q) => $q->where('user_id', $userId)])
            ->first();
    }

    public function markAsRead(int $announcementId, int $userId): void
    {
        AnnouncementRead::firstOrCreate([
            'announcement_id' => $announcementId,
            'user_id' => $userId,
        ], [
            'read_at' => now()
        ]);
    }
}