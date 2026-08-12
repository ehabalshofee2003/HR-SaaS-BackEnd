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

    public function getMyLeaveRequests(int $employeeId, int $perPage, ?string $status = null)
    {
        return $this->leaveRequestRepository->getEmployeeLeaveRequests($employeeId, $perPage, $status);
    }

    public function getMyLeaveRequestById(int $employeeId, int $leaveRequestId): ?LeaveRequest
    {
        return $this->leaveRequestRepository->findEmployeeLeaveRequest($employeeId, $leaveRequestId);
    }
    public function submitLeaveRequest(int $employeeId, int $userId, array $data, array $files = []): LeaveRequest
    {
        $leaveTypeId = $this->leaveRequestRepository->findLeaveTypeIdByCode($data['company_id'], $data['leave_type']);

        if (!$leaveTypeId) {
            throw new Exception('نوع الإجازة المحدد غير متاح حالياً.');
        }

        $uploadedPaths = [];

        try {
            $storedFiles = [];
            foreach ($files as $file) {
                $path = $file->store('leave_attachments/' . $employeeId, 'public');
                $uploadedPaths[] = $path;
                $storedFiles[] = [
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                ];
            }

            DB::beginTransaction();

            $insertData = [
                'employee_id' => $employeeId,
                'company_id' => $data['company_id'],
                'leave_type_id' => $leaveTypeId,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'],
                'status' => 'pending',
            ];

            $leaveRequest = $this->leaveRequestRepository->create($insertData);

            foreach ($storedFiles as $file) {
                $this->leaveRequestRepository->attachDocument([
                    'company_id' => $data['company_id'],
                    'leave_request_id' => $leaveRequest->id,
                    'file_name' => $file['file_name'],
                    'file_path' => $file['file_path'],
                    'mime_type' => $file['mime_type'],
                    'uploaded_by' => $userId,
                ]);
            }

            DB::commit();
            return $leaveRequest;

        } catch (Exception $e) {
            if (isset($leaveRequest)) {
                DB::rollBack();
            }

            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    }
    public function getEmployeeBalanceSummary()
    {
        $user = $this->getAuthenticatedUser();
        return $this->leaveBalanceRepository->getEmployeeCurrentYearBalances($user->id);
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
    public function getCombinedBalance(int $employeeUserId): array
    {
        return $this->leaveBalanceRepository->getCombinedBalance($employeeUserId);
    }

    public function getAttachmentsFor(int $leaveRequestId): array
    {
        return $this->leaveRequestRepository->getAttachments($leaveRequestId);
    }
}