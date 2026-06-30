<?php

namespace App\Services\Hr;

use App\Repositories\Hr\ExceptionRequestRepository;
use App\Http\Requests\Employee\StoreExceptionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExceptionRequestService
{
    public function __construct(
        private ExceptionRequestRepository $repository
    ) {}

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
        // 1. رفع الملف خارج الـ Transaction (Resilience Pattern)
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
            // 2. Compensating Action: حذف الملف إذا فشلت قاعدة البيانات
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
            return null; // سيتعامل معه الكنترولر لرجاع 404
        }

        return $this->repository->updateStatus($exceptionRequest, 'cancelled') ? $exceptionRequest->refresh() : null;
    }
}