<?php

namespace App\Services\Supervisor;

use App\Repositories\Supervisor\LeaveRequestRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class LeaveRequestService
{
    private const AUTO_APPROVE_MAX_DAYS = 3;

    public function __construct(
        protected LeaveRequestRepository $leaveRequestRepository
    ) {}

    public function list(int $supervisorId, array $filters)
    {
        return $this->leaveRequestRepository->paginateForSupervisor($supervisorId, $filters);
    }

    public function getDetails(int $id, int $supervisorId): object
    {
        $leaveRequest = $this->leaveRequestRepository->findForSupervisor($id, $supervisorId);

        if (!$leaveRequest) {
            throw new Exception('طلب الإجازة غير موجود.', 404);
        }

        $balance = $this->leaveRequestRepository->getBalanceForEmployee(
            $leaveRequest->employee_id,
            $leaveRequest->leave_type_id
        );

        return (object) [
            'leave_request' => $leaveRequest,
            'employee_balance' => $balance,
        ];
    }

    public function approve(int $id, int $supervisorId, ?string $notes = null): object
    {
        $leaveRequest = $this->leaveRequestRepository->findForSupervisor($id, $supervisorId);

        if (!$leaveRequest) {
            throw new Exception('طلب الإجازة غير موجود.', 404);
        }

        if ($leaveRequest->status !== 'pending') {
            throw new Exception('لا يمكن مراجعة هذا الطلب — تمت مراجعته مسبقًا.', 422);
        }

        $days = Carbon::parse($leaveRequest->start_date)->diffInDays(Carbon::parse($leaveRequest->end_date)) + 1;

        return DB::transaction(function () use ($leaveRequest, $supervisorId, $days) {
            if ($days <= self::AUTO_APPROVE_MAX_DAYS) {
                $this->leaveRequestRepository->updateStatus($leaveRequest->id, [
                    'status' => 'approved',
                    'approver_id' => $supervisorId,
                    'approved_at' => now(),
                ]);

                $this->leaveRequestRepository->deductBalance(
                    $leaveRequest->employee_id,
                    $leaveRequest->leave_type_id,
                    $days
                );

                // TODO: إشعار الموظف بالاعتماد
            } else {
                $this->leaveRequestRepository->updateStatus($leaveRequest->id, [
                    'status' => 'pending_manager',
                ]);

                // TODO: إشعار مدير الفرع بوجود طلب بانتظار مراجعته
            }

            return $this->leaveRequestRepository->findForSupervisor($leaveRequest->id, $supervisorId);
        });
    }

    public function reject(int $id, int $supervisorId, string $reason): object
    {
        $leaveRequest = $this->leaveRequestRepository->findForSupervisor($id, $supervisorId);

        if (!$leaveRequest) {
            throw new Exception('طلب الإجازة غير موجود.', 404);
        }

        if ($leaveRequest->status !== 'pending') {
            throw new Exception('لا يمكن مراجعة هذا الطلب — تمت مراجعته مسبقًا.', 422);
        }

        $this->leaveRequestRepository->updateStatus($leaveRequest->id, [
            'status' => 'rejected',
            'approver_id' => $supervisorId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // TODO: إشعار الموظف بالرفض

        return $this->leaveRequestRepository->findForSupervisor($id, $supervisorId);
    }
}