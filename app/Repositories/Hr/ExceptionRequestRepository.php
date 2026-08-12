<?php

namespace App\Repositories\Hr;

use App\Models\Hr\ExceptionRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExceptionRequestRepository
{
    // ================= دوال Branch Manager (جديدة) =================

    public function paginateForBranch(int $branchId, array $filters, int $perPage = 15)
    {
        $query = DB::table('exception_requests')
            ->join('employee_details', 'exception_requests.employee_id', '=', 'employee_details.id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->join('user_profiles', 'employee_details.user_id', '=', 'user_profiles.user_id')
            ->join('exception_types', 'exception_requests.exception_type_id', '=', 'exception_types.id')
            ->leftJoin('user_profiles as supervisor_profiles', 'employee_details.supervisor_id', '=', DB::raw('supervisor_profiles.user_id'))
            ->where('departments.branch_id', $branchId)
            ->where('exception_requests.status', 'supervisor_reviewed')
            ->whereNull('exception_requests.deleted_at')
            ->select(
                'exception_requests.id',
                'exception_requests.request_date',
                'exception_requests.reason',
                'exception_requests.duration_minutes',
                'exception_requests.attachment',
                'exception_requests.status',
                'exception_requests.created_at',
                'user_profiles.full_name as employee_name',
                'supervisor_profiles.full_name as supervisor_name',
                'exception_types.name as exception_type_name'
            );

        if (!empty($filters['employee_id'])) {
            $query->where('employee_details.user_id', $filters['employee_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('exception_requests.exception_type_id', $filters['type']);
        }

        return $query->orderByDesc('exception_requests.created_at')->paginate($perPage);
    }

    public function findForBranch(int $id, int $branchId): ?object
    {
        return DB::table('exception_requests')
            ->join('employee_details', 'exception_requests.employee_id', '=', 'employee_details.id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('exception_requests.id', $id)
            ->where('departments.branch_id', $branchId)
            ->whereNull('exception_requests.deleted_at')
            ->select('exception_requests.*')
            ->first();
    }

    public function updateStatus(int $id, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('exception_requests')->where('id', $id)->update($data);
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

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

    public function updateStatus_Legacy(ExceptionRequest $exceptionRequest, string $status): bool
    {
        return $exceptionRequest->update(['status' => $status]);
    }
    public function getActiveTypes(): array
{
    return DB::table('exception_types')
        ->where('is_active', true)
        ->select('id', 'name', 'slug')
        ->get()
        ->toArray();
}
}