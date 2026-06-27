<?php

namespace App\Repositories\Hr;

use App\Models\Hr\Resignation;

class ResignationRepository
{
    public function create(array $data): Resignation
    {
        return Resignation::create($data);
    }

    public function getEmployeeResignations(int $employeeUserId, int $perPage = 15)
    {
        return Resignation::where('employee_user_id', $employeeUserId)
            ->with('supervisor.profile')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findEmployeeResignationById(int $id, int $employeeUserId): ?Resignation
    {
        return Resignation::where('id', $id)
            ->where('employee_user_id', $employeeUserId)
            ->with('supervisor.profile')
            ->first();
    }

    public function updateStatus(Resignation $resignation, string $status): bool
    {
        return $resignation->update(['status' => $status]);
    }
}