<?php

namespace App\Services\Hr;

use App\Repositories\Hr\ExceptionRequestRepository;
use App\Http\Requests\Employee\StoreExceptionRequest;
use App\Models\Identity\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class ExceptionRequestService
{
    public function __construct(
        private ExceptionRequestRepository $repository
    ) {}

    // ================= دوال Branch Manager (جديدة) =================

    public function list(User $manager, array $filters)
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->repository->paginateForBranch($branchId, $filters);
    }

    public function getDetails(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $request = $this->repository->findForBranch($id, $branchId);

        if (!$request) {
            throw new Exception('الطلب غير موجود.', 404);
        }

        return $request;
    }

    public function forwardToOwner(int $id, User $manager, string $note): object
    {
        $branchId = $manager->getCurrentBranchId();
        $request = $this->repository->findForBranch($id, $branchId);

        if (!$request) {
            throw new Exception('الطلب غير موجود.', 404);
        }

        if ($request->status !== 'supervisor_reviewed') {
            throw new Exception('لا يمكن إحالة إلا الطلبات التي راجعها المشرف بالفعل.');
        }

        $this->repository->updateStatus($id, [
            'status' => 'owner_reviewed',
            'manager_note' => $note,
        ]);

        // TODO: إشعار المالك بالطلب المُحال (يتضمن manager_note بمحتوى الإشعار)

        return $this->repository->findForBranch($id, $branchId);
    }

    public function reject(int $id, User $manager, string $reason): object
    {
        $branchId = $manager->getCurrentBranchId();
        $request = $this->repository->findForBranch($id, $branchId);

        if (!$request) {
            throw new Exception('الطلب غير موجود.', 404);
        }

        if ($request->status !== 'supervisor_reviewed') {
            throw new Exception('لا يمكن الرفض إلا للطلبات التي راجعها المشرف بالفعل.');
        }

        $this->repository->updateStatus($id, [
            'status' => 'rejected',
            'approver_id' => $manager->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // TODO: إشعار الموظف والمشرف بالرفض

        return $this->repository->findForBranch($id, $branchId);
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس، فقط اسم الدالة صُحح) =================

    public function getAll($user)
    {
        $companyId = $user->getCurrentCompanyId();
        $employeeId = $user->employeeDetail->id;

        return $this->repository->getEmployeeExceptions($employeeId, $companyId);
    }

    public function getById($user, $id)
    {
        $companyId = $user->getCurrentCompanyId();
        $employeeId = $user->employeeDetail->id;

        return $this->repository->findEmployeeException((int)$id, $employeeId, $companyId);
    }

    public function store($user, StoreExceptionRequest $request)
    {
        $companyId = $user->getCurrentCompanyId();
        $employeeId = $user->employeeDetail->id;

        $path = null;
        if (isset($request->attachment)) {
            $path = $request->attachment->store('exceptions', 'public');
        }

        $data = [
            'company_id'         => $companyId,
            'employee_id'        => $employeeId,
            'exception_type_id'  => $request->exception_type_id,
            'request_date'       => $request->request_date,
            'start_time'         => $request->start_time,
            'end_time'           => $request->end_time,
            'duration_minutes'   => $request->duration_minutes,
            'reason'             => $request->reason,
            'attachment'         => $path,
        ];

        try {
            DB::beginTransaction();
            $exceptionRequest = $this->repository->create($data);
            DB::commit();

            return $exceptionRequest;

        } catch (\Exception $e) {
            DB::rollBack();
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }
    }

    public function cancel($user, $id)
    {
        $companyId = $user->getCurrentCompanyId();
        $employeeId = $user->employeeDetail->id;

        $exceptionRequest = $this->repository->findEmployeeException((int)$id, $employeeId, $companyId);

        if (!$exceptionRequest || $exceptionRequest->status !== 'pending') {
            return null;
        }

        return $this->repository->updateStatus_Legacy($exceptionRequest, 'cancelled') ? $exceptionRequest->refresh() : null;
    }
}