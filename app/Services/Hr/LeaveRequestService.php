<?php

namespace App\Services\Hr;

use App\Models\Identity\User;
use App\Repositories\Hr\LeaveRequestRepository;
use App\Repositories\Hr\LeaveBalanceRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Hr\LeaveRequest;
use Carbon\Carbon;
use Exception;

class LeaveRequestService
{
    public function __construct(
        private LeaveRequestRepository $leaveRequestRepository,
        private LeaveBalanceRepository $leaveBalanceRepository
    ) {}

    // ================= دوال Branch Manager (جديدة) =================

    public function list(User $manager, array $filters)
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->leaveRequestRepository->paginateForBranch($branchId, $filters);
    }

    public function getDetails(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $request = $this->leaveRequestRepository->findForBranch($id, $branchId);

        if (!$request) {
            throw new Exception('طلب الإجازة غير موجود.', 404);
        }

        return $request;
    }

    public function approve(int $id, User $manager, ?string $note): object
    {
        $branchId = $manager->getCurrentBranchId();
        $companyId = $manager->getCurrentCompanyId();
        $request = $this->leaveRequestRepository->findForBranch($id, $branchId);

        if (!$request) {
            throw new Exception('طلب الإجازة غير موجود.', 404);
        }

        if ($request->status !== 'pending_manager') {
            throw new Exception('لا يمكن الموافقة إلا على طلبات محالة لمدير الفرع.');
        }

        $leaveType = $this->leaveRequestRepository->getLeaveTypeInfo($request->leave_type_id);
        $days = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) + 1;

        return DB::transaction(function () use ($id, $request, $manager, $note, $companyId, $leaveType, $days, $branchId) {

            // خصم الرصيد فقط إذا لم تكن الإجازة Unpaid
            if ($leaveType && $leaveType->code !== 'unpaid') {
                $policy = $this->leaveRequestRepository->getPolicyForLeaveType($companyId, $leaveType->code);

                if ($policy) {
                    $year = Carbon::parse($request->start_date)->year;
                    $balance = $this->leaveBalanceRepository->findBalance($request->employee_id, $policy->id, $year);

                    if ($balance && $balance->remaining_days < $days) {
                        throw new Exception('لا يمكن الموافقة — الإجازة تتجاوز الرصيد المتبقي للموظف.');
                    }

                    if ($balance) {
                        $this->leaveBalanceRepository->deductDays($balance->id, $days);
                    }
                }
            }

            $this->leaveRequestRepository->updateStatus($id, [
                'status' => 'approved',
                'approver_id' => $manager->id,
                'approved_at' => Carbon::now(),
            ]);

            // TODO: إشعار الموظف بالموافقة

            return $this->leaveRequestRepository->findForBranch($id, $branchId);
        });
    }

    public function reject(int $id, User $manager, string $reason): object
    {
        $branchId = $manager->getCurrentBranchId();
        $request = $this->leaveRequestRepository->findForBranch($id, $branchId);

        if (!$request) {
            throw new Exception('طلب الإجازة غير موجود.', 404);
        }

        if ($request->status !== 'pending_manager') {
            throw new Exception('لا يمكن الرفض إلا لطلبات محالة لمدير الفرع.');
        }

        $this->leaveRequestRepository->updateStatus($id, [
            'status' => 'rejected',
            'approver_id' => $manager->id,
            'approved_at' => Carbon::now(),
            'rejection_reason' => $reason,
        ]);

        // TODO: إشعار الموظف بالرفض

        return $this->leaveRequestRepository->findForBranch($id, $branchId);
    }

    public function getEmployeeBalances(int $employeeUserId, User $manager): array
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->leaveBalanceRepository->getForEmployeeInBranch($employeeUserId, $branchId);
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

    public function getMyLeaveRequests(int $employeeId, int $perPage)
    {
        return $this->leaveRequestRepository->getEmployeeLeaveRequests($employeeId, $perPage);
    }

    public function getMyLeaveRequestById(int $employeeId, int $leaveRequestId): ?LeaveRequest
    {
        return $this->leaveRequestRepository->findEmployeeLeaveRequest($employeeId, $leaveRequestId);
    }

    public function submitLeaveRequest(int $employeeId, array $data, $file = null): LeaveRequest
    {
        $attachmentPath = null;

        if ($file) {
            $attachmentPath = $file->store('leave_attachments/' . $employeeId, 'public');
        }

        DB::beginTransaction();
        try {
            $data['employee_id'] = $employeeId;
            $data['status'] = 'pending';
            $data['attachment'] = $attachmentPath;

            $leaveRequest = $this->leaveRequestRepository->create($data);

            DB::commit();
            return $leaveRequest;

        } catch (Exception $e) {
            DB::rollBack();

            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            throw $e;
        }
    }

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

    private function getAuthenticatedUser(): User
    {
        $user = User::find(Auth::id());
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        return $user;
    }
}