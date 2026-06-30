<?php

namespace App\Repositories\Hr;

use App\Models\Hr\LeaveRequest;
use Illuminate\Pagination\LengthAwarePaginator;
USe Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LeaveRequestRepository
{
    public function getEmployeeLeaveRequests(int $employeeId, int $perPage = 15): LengthAwarePaginator
    {
        return LeaveRequest::where('employee_id', $employeeId)
            ->with(['leaveType', 'approver']) // Eager Loading لعلاقات نوع الإجازة والموافق
            ->latest()
            ->paginate($perPage);
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
        // أضف هذه الدالة داخل الملف الموجود مسبقاً
    public function findPendingRequestById(int $id, int $employeeUserId): ?LeaveRequest
    {
        // استعلام خام يضرب الداتا بيز مباشرة بدون أي تدخل من لارافيل
        $record = DB::table('leave_requests')
            ->where('id', $id)
            ->where('employee_id', $employeeUserId)
            ->where('status', 'pending')
            ->whereNull('deleted_at') // نستبعد المحذوفين يدوياً
            ->first();

        // إذا وجدناه، نرجعه كـ Model لكي نتمكن من عمل ->update() عليه
        if ($record) {
            return LeaveRequest::find($record->id);
        }

        return null;
    }
}   