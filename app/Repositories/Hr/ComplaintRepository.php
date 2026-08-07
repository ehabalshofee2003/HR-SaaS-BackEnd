<?php

namespace App\Repositories\Hr;

use App\Models\Hr\Complaint;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComplaintRepository
{
    protected $model;

    public function __construct(Complaint $model)
    {
        $this->model = $model;
    }

    // ================= دوال Branch Manager (جديدة) =================

    public function paginateForBranch(int $branchId, array $filters, int $perPage = 15)
    {
        $query = DB::table('complaints')
            ->leftJoin('departments', 'complaints.department_id', '=', 'departments.id')
            ->leftJoin('users as against', 'complaints.against_user_id', '=', 'against.id')
            ->leftJoin('user_profiles', 'complaints.user_id', '=', 'user_profiles.user_id')
            ->where(function ($q) use ($branchId) {
                $q->where('departments.branch_id', $branchId)
                  ->orWhere('against.branch_id', $branchId);
            })
            ->whereNull('complaints.deleted_at')
            ->select(
                'complaints.id',
                'complaints.subject',
                'complaints.status',
                'complaints.is_anonymous',
                'complaints.against_user_id',
                'complaints.created_at',
                'complaints.updated_at',
                DB::raw("CASE WHEN complaints.is_anonymous = 1 THEN 'مجهول' ELSE user_profiles.full_name END as employee_name")
            );

        if (!empty($filters['status'])) {
            $query->where('complaints.status', $filters['status']);
        }

        return $query->orderByDesc('complaints.created_at')->paginate($perPage);
    }

    public function findForBranch(int $id, int $branchId): ?object
    {
        return DB::table('complaints')
            ->leftJoin('departments', 'complaints.department_id', '=', 'departments.id')
            ->leftJoin('users as against', 'complaints.against_user_id', '=', 'against.id')
            ->where('complaints.id', $id)
            ->where(function ($q) use ($branchId) {
                $q->where('departments.branch_id', $branchId)
                  ->orWhere('against.branch_id', $branchId);
            })
            ->whereNull('complaints.deleted_at')
            ->select('complaints.*')
            ->first();
    }

    public function updateStatus(int $id, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('complaints')->where('id', $id)->update($data);
    }

    public function addMessage(int $complaintId, int $senderId, string $message): int
    {
        return DB::table('complaint_messages')->insertGetId([
            'complaint_id' => $complaintId,
            'sender_user_id' => $senderId,
            'message' => $message,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function getThread(int $complaintId): array
    {
        return DB::table('complaint_messages')
            ->join('user_profiles', 'complaint_messages.sender_user_id', '=', 'user_profiles.user_id')
            ->join('users', 'complaint_messages.sender_user_id', '=', 'users.id')
            ->where('complaint_messages.complaint_id', $complaintId)
            ->select(
                'complaint_messages.id',
                'complaint_messages.message',
                'complaint_messages.created_at',
                'user_profiles.full_name as sender_name',
                'users.user_type as sender_type'
            )
            ->orderBy('complaint_messages.created_at')
            ->get()
            ->toArray();
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

    public function create(array $data): Complaint
    {
        return $this->model->create($data);
    }

    public function getByUserId(int $userId)
    {
        return $this->model->where('user_id', $userId)->latest()->get();
    }
}