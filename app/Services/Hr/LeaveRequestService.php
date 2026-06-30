<?php

namespace App\Services\Hr;

use App\Models\Identity\User;
use App\Repositories\Hr\LeaveRequestRepository;
use App\Repositories\Hr\LeaveBalanceRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Hr\LeaveRequest;
use Exception;

class LeaveRequestService
{
    public function __construct(
        private LeaveRequestRepository $leaveRequestRepository,
        private LeaveBalanceRepository $leaveBalanceRepository
    ) {}

    public function getMyLeaveRequests(int $employeeId, int $perPage)
    {
        // تم تصحيح الخطأ هنا: repository -> leaveRequestRepository
        return $this->leaveRequestRepository->getEmployeeLeaveRequests($employeeId, $perPage);
    }

    public function getMyLeaveRequestById(int $employeeId, int $leaveRequestId): ?LeaveRequest
    {
        // تم تصحيح الخطأ هنا: repository -> leaveRequestRepository
        return $this->leaveRequestRepository->findEmployeeLeaveRequest($employeeId, $leaveRequestId);
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

            // تم تصحيح الخطأ هنا: repository -> leaveRequestRepository
            $leaveRequest = $this->leaveRequestRepository->create($data);

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
    
    // 1. دالة جلب الرصيد
    public function getBalance()
    {
        $user = $this->getAuthenticatedUser();
        $balances = $this->leaveBalanceRepository->getEmployeeCurrentYearBalances($user->id);
        
        return [
            'success' => true,
            'code' => 200,
            'data' => $balances
        ];
    }

    // 2. دالة إلغاء الطلب
    public function cancelRequest($id)
    {
        $user = $this->getAuthenticatedUser();
        
        $request = $this->leaveRequestRepository->findPendingRequestById((int) $id, $user->id);

        if (!$request) {
            return [
                'success' => false,
                'message' => 'Leave request not found or cannot be cancelled.',
                'code' => 404
            ];
        }

        $request->update(['status' => 'cancelled']);

        return [
            'success' => true,
            'message' => 'Leave request cancelled successfully.',
            'code' => 200
        ];
    }

    /**
     * قاعدة Auth Type Hinting الصارمة
     */
    private function getAuthenticatedUser(): User
    {
        $user = User::find(Auth::id());
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        return $user;
    }
}