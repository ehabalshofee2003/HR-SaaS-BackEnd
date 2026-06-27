<?php

namespace App\Services\Hr;

use App\Repositories\Hr\LeaveRequestRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Hr\LeaveRequest;
use Exception;

class LeaveRequestService
{
    public function __construct(private LeaveRequestRepository $repository) {}

    public function getMyLeaveRequests(int $employeeId, int $perPage)
    {
        return $this->repository->getEmployeeLeaveRequests($employeeId, $perPage);
    }

    public function getMyLeaveRequestById(int $employeeId, int $leaveRequestId): ?LeaveRequest
    {
        return $this->repository->findEmployeeLeaveRequest($employeeId, $leaveRequestId);
    }

    public function submitLeaveRequest(int $employeeId, array $data, $file = null): LeaveRequest
    {
        $attachmentPath = null;

        // 1. رفع الملف خارج الـ Transaction (Resilience Pattern)
        if ($file) {
            $attachmentPath = $file->store('leave_attachments/' . $employeeId, 'public');
        }

        // 2. بدء Transaction لقاعدة البيانات
        DB::beginTransaction();
        try {
            $data['employee_id'] = $employeeId;
            $data['status'] = 'pending'; // الحالة الافتراضية
            $data['attachment'] = $attachmentPath;

            $leaveRequest = $this->repository->create($data);

            DB::commit();
            return $leaveRequest;

        } catch (Exception $e) {
            DB::rollBack();

            // 3. Compensating Action: حذف الملف إذا فشلت قاعدة البيانات
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            throw $e; // إعادة رمي الخطأ للـ Controller ليقوم بالتعامل معه
        }
    }
}