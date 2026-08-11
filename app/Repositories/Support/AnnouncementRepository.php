<?php

namespace App\Repositories\Support;

use App\Models\Support\Announcement;
use App\Models\Support\AnnouncementRead;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnnouncementRepository
{
    // ================= دوال Branch Manager (جديدة) =================

    public function departmentIdsInBranch(int $branchId): array
    {
        return DB::table('departments')->where('branch_id', $branchId)->pluck('id')->toArray();
    }

    public function employeeIdsInBranch(int $branchId): array
    {
        return DB::table('employee_details')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('departments.branch_id', $branchId)
            ->pluck('employee_details.user_id')
            ->toArray();
    }

    public function paginateForBranch(int $branchId, array $filters, int $perPage = 15)
    {
        $deptIds = $this->departmentIdsInBranch($branchId);
        $empIds = $this->employeeIdsInBranch($branchId);

        $query = DB::table('announcements')
            ->where(function ($q) use ($branchId, $deptIds, $empIds) {
                $q->where(function ($q2) use ($branchId) {
                    $q2->where('target_type', 'branch')->where('target_id', $branchId);
                })
                ->orWhere(function ($q2) use ($deptIds) {
                    $q2->where('target_type', 'department')->whereIn('target_id', $deptIds);
                })
                ->orWhere(function ($q2) use ($empIds) {
                    $q2->where('target_type', 'employee')->whereIn('target_id', $empIds);
                });
            })
            ->whereNull('deleted_at')
            ->select('id', 'title', 'content', 'target_type', 'target_id', 'start_date', 'end_date', 'is_active', 'created_at');

        if (!empty($filters['target'])) {
            $query->where('target_type', $filters['target'] === 'all' ? 'branch' : $filters['target']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findForBranch(int $id, int $branchId): ?object
    {
        $deptIds = $this->departmentIdsInBranch($branchId);
        $empIds = $this->employeeIdsInBranch($branchId);

        return DB::table('announcements')
            ->where('id', $id)
            ->where(function ($q) use ($branchId, $deptIds, $empIds) {
                $q->where(function ($q2) use ($branchId) {
                    $q2->where('target_type', 'branch')->where('target_id', $branchId);
                })
                ->orWhere(function ($q2) use ($deptIds) {
                    $q2->where('target_type', 'department')->whereIn('target_id', $deptIds);
                })
                ->orWhere(function ($q2) use ($empIds) {
                    $q2->where('target_type', 'employee')->whereIn('target_id', $empIds);
                });
            })
            ->whereNull('deleted_at')
            ->first();
    }

    public function departmentBelongsToBranch(int $departmentId, int $branchId): bool
    {
        return DB::table('departments')->where('id', $departmentId)->where('branch_id', $branchId)->exists();
    }

    public function employeeBelongsToBranch(int $employeeUserId, int $branchId): bool
    {
        return in_array($employeeUserId, $this->employeeIdsInBranch($branchId));
    }

    public function create(array $data): int
    {
        return DB::table('announcements')->insertGetId([
            'company_id' => $data['company_id'],
            'created_by' => $data['created_by'],
            'title' => $data['title'],
            'content' => $data['content'],
            'target_type' => $data['target_type'],
            'target_id' => $data['target_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function attachDocument(array $data): void
    {
        DB::table('user_documents')->insert([
            'company_id' => $data['company_id'],
            'documentable_type' => 'App\\Models\\Support\\Announcement',
            'documentable_id' => $data['announcement_id'],
            'type' => 'announcement_attachment',
            'file_name' => $data['file_name'],
            'file_path' => $data['file_path'],
            'mime_type' => $data['mime_type'] ?? null,
            'uploaded_by' => $data['uploaded_by'],
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function getAttachments(int $announcementId): array
    {
        return DB::table('user_documents')
            ->where('documentable_type', 'App\\Models\\Support\\Announcement')
            ->where('documentable_id', $announcementId)
            ->get()
            ->toArray();
    }

    public function getReaders(int $announcementId): array
    {
        return DB::table('announcement_reads')
            ->join('user_profiles', 'announcement_reads.user_id', '=', 'user_profiles.user_id')
            ->where('announcement_id', $announcementId)
            ->select('user_profiles.full_name', 'announcement_reads.read_at')
            ->orderBy('announcement_reads.read_at')
            ->get()
            ->toArray();
    }

    public function updateStatus(int $id, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('announcements')->where('id', $id)->update($data);
    }

    public function softDelete(int $id): void
    {
        DB::table('announcements')->where('id', $id)->update(['deleted_at' => Carbon::now()]);
    }

    // ================= دوال Employee Mobile (الأصلية — مُصححة سابقاً، لم تُمس الآن) =================

    private function applyTargetingScope($query, int $userId, ?int $branchId, ?int $departmentId)
    {
        return $query->where(function ($q) use ($userId, $branchId, $departmentId) {
            $q->where('target_type', 'all')
              ->orWhere(function ($q2) use ($branchId) {
                  $q2->where('target_type', 'branch')->where('target_id', $branchId);
              })
              ->orWhere(function ($q2) use ($departmentId) {
                  $q2->where('target_type', 'department')->where('target_id', $departmentId);
              })
              ->orWhere(function ($q2) use ($userId) {
                  $q2->where('target_type', 'employee')->where('target_id', $userId);
              });
        });
    }

    public function getEmployeeAnnouncements(int $companyId, int $userId, ?int $branchId, ?int $departmentId, int $perPage = 15)
    {
        $query = Announcement::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('start_date', '<=', Carbon::today()->toDateString())
            ->where('end_date', '>=', Carbon::today()->toDateString());

        $query = $this->applyTargetingScope($query, $userId, $branchId, $departmentId);

        return $query->with(['reads' => fn($q) => $q->where('user_id', $userId)])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findAnnouncementForEmployee(int $id, int $companyId, int $userId, ?int $branchId, ?int $departmentId): ?Announcement
    {
        $query = Announcement::where('id', $id)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('start_date', '<=', Carbon::today()->toDateString())
            ->where('end_date', '>=', Carbon::today()->toDateString());

        $query = $this->applyTargetingScope($query, $userId, $branchId, $departmentId);

        return $query->with(['reads' => fn($q) => $q->where('user_id', $userId)])->first();
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