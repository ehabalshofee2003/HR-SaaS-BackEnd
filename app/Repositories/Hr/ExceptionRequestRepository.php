<?php

namespace App\Repositories\Hr;

use App\Models\Hr\ExceptionRequest;
use Illuminate\Support\Facades\DB;

class ExceptionRequestRepository
{
    public function getEmployeeExceptions(int $employeeId, int $companyId)
    {
        return ExceptionRequest::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->latest()
            ->paginate(15);
    }

    public function findEmployeeException(int $id, int $employeeId, int $companyId): ?ExceptionRequest
    {
        return ExceptionRequest::where('id', $id)
            ->where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->first();
    }

    public function create(array $data): ExceptionRequest
    {
        return ExceptionRequest::create($data);
    }

    public function updateStatus(ExceptionRequest $exceptionRequest, string $status): bool
    {
        return $exceptionRequest->update(['status' => $status]);
    }
}