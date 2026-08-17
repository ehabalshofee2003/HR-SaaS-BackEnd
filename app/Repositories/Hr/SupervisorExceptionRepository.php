<?php

namespace App\Repositories\Hr;

use Illuminate\Support\Facades\DB;

class SupervisorExceptionRepository
{
    public function listForSupervisor(int $supervisorId, array $filters): array
    {
        $query = DB::table('exception_requests as er')
            ->join('employee_details as ed', 'ed.id', '=', 'er.employee_id')
            ->join('users as u', 'u.id', '=', 'ed.user_id')
            ->join('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->join('exception_types as et', 'et.id', '=', 'er.exception_type_id')
            ->where('ed.supervisor_id', $supervisorId)
            ->whereNull('er.deleted_at');

        $query->where('er.status', $filters['status'] ?? 'pending');

        return $query->select([
            'er.id', 'p.full_name as employee_name', 'et.name as type',
            'er.request_date as date', 'er.status', 'er.reason', 'er.rejection_reason',
        ])->orderByDesc('er.request_date')->get()->all();
    }

    public function findForSupervisor(int $id, int $supervisorId): ?object
    {
        return DB::table('exception_requests as er')
            ->join('employee_details as ed', 'ed.id', '=', 'er.employee_id')
            ->join('user_profiles as p', 'p.user_id', '=', 'ed.user_id')
            ->join('exception_types as et', 'et.id', '=', 'er.exception_type_id')
            ->where('er.id', $id)
            ->where('ed.supervisor_id', $supervisorId)
            ->whereNull('er.deleted_at')
            ->select([
                'er.id', 'p.full_name as employee_name', 'et.name as type',
                'er.request_date as date', 'er.status', 'er.reason', 'er.rejection_reason',
            ])
            ->first();
    }

    public function updateStatus(int $id, array $data): void
    {
        $data['updated_at'] = now();
        DB::table('exception_requests')->where('id', $id)->update($data);
    }
}