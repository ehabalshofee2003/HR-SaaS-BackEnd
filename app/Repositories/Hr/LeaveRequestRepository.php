<?php

namespace App\Repositories\Hr;

use App\Models\Hr\LeaveRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveRequestRepository
{
    // ================= دوال Branch Manager (جديدة) =================

    public function paginateForBranch(int $branchId, array $filters, int $perPage = 15)
    {
        $query = DB::table('leave_requests')
            ->join('employee_details', 'leave_requests.employee_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->join('user_profiles', 'leave_requests.employee_id', '=', 'user_profiles.user_id')
            ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->where('departments.branch_id', $branchId)
            ->whereNull('leave_requests.deleted_at')
            ->select(
                'leave_requests.id',
                'leave_requests.start_date',
                'leave_requests.end_date',
                'leave_requests.reason',
                'leave_requests.attachment',
                'leave_requests.status',
                'leave_requests.created_at',
                'user_profiles.full_name as employee_name',
                'leave_types.name as leave_type_name',
                'employee_details.department_id'
            );

        if (!empty($filters['status'])) {
            $query->where('leave_requests.status', $filters['status']);
        } else {
            // افتراضياً: نعرض فقط الطلبات المحالة فعلياً لمدير الفرع
            $query->where('leave_requests.status', 'pending_manager');
        }

        if (!empty($filters['type'])) {
            $query->where('leave_requests.leave_type_id', $filters['type']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('leave_requests.employee_id', $filters['employee_id']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('leave_requests.start_date', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('leave_requests.end_date', '<=', $filters['to']);
        }

        return $query->orderByRaw("FIELD(leave_requests.status, 'pending_manager') DESC")
            ->orderByDesc('leave_requests.created_at')
            ->paginate($perPage);
    }

    public function findForBranch(int $id, int $branchId): ?object
    {
        return DB::table('leave_requests')
            ->join('employee_details', 'leave_requests.employee_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('leave_requests.id', $id)
            ->where('departments.branch_id', $branchId)
            ->whereNull('leave_requests.deleted_at')
            ->select('leave_requests.*')
            ->first();
    }

    public function updateStatus(int $id, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('leave_requests')->where('id', $id)->update($data);
    }

    public function getLeaveTypeInfo(int $leaveTypeId): ?object
    {
        return DB::table('leave_types')->where('id', $leaveTypeId)->first();
    }

 

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

    public function getEmployeeLeaveRequests(int $employeeId, int $perPage = 15, ?string $status = null): LengthAwarePaginator
    {
        $query = LeaveRequest::where('employee_id', $employeeId)
            ->with(['leaveType', 'approver'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
      }

        return $query->paginate($perPage);
    }

    public function findEmployeeLeaveRequest(int $employeeId, int $leaveRequestId): ?LeaveRequest
    {
        return LeaveRequest::where('id', $leaveRequestId)
            ->where('employee_id', $employeeId)
            ->with(['leaveType', 'approver'])
            ->first();
    }

    public function create(array $data): LeaveRequest
    {
        return LeaveRequest::create($data);
    }

    public function findPendingRequestById(int $id, int $employeeUserId): ?LeaveRequest
    {
        $record = DB::table('leave_requests')
            ->where('id', $id)
            ->where('employee_id', $employeeUserId)
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->first();

        if ($record) {
            return LeaveRequest::find($record->id);
        }

        return null;
    }
    public function findLeaveTypeIdByCode(int $companyId, string $code): ?int
    {
        return DB::table('leave_types')
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->where('is_active', true)
            ->value('id');
    }
    public function attachDocument(array $data): void
{
    DB::table('user_documents')->insert([
        'company_id' => $data['company_id'],
        'documentable_type' => 'App\\Models\\Hr\\LeaveRequest',
        'documentable_id' => $data['leave_request_id'],
        'type' => 'leave_attachment',
        'file_name' => $data['file_name'],
        'file_path' => $data['file_path'],
        'mime_type' => $data['mime_type'] ?? null,
        'uploaded_by' => $data['uploaded_by'],
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);
}

    public function getAttachments(int $leaveRequestId): array
    {
        return DB::table('user_documents')
            ->where('documentable_type', 'App\\Models\\Hr\\LeaveRequest')
            ->where('documentable_id', $leaveRequestId)
            ->get()
            ->toArray();
    }


}