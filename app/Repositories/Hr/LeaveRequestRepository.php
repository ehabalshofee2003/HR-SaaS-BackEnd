<?php

namespace App\Repositories\Hr;

use App\Models\Hr\LeaveRequest;
use Illuminate\Pagination\LengthAwarePaginator;

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
}   