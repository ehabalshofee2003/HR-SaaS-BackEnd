<?php

namespace App\Repositories\Owner;

use App\Repositories\Interfaces\Owner\ExceptionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ExceptionRepository implements ExceptionRepositoryInterface
{
    private function baseQuery(int $companyId)
    {
        return DB::table('exception_requests as er')
            ->join('employee_details as ed', 'ed.id', '=', 'er.employee_id')
            ->join('users as eu', 'eu.id', '=', 'ed.user_id')
            ->join('user_profiles as eup', 'eup.user_id', '=', 'eu.id')
            ->join('departments as d', 'd.id', '=', 'ed.department_id')
            ->join('branches as b', 'b.id', '=', 'd.branch_id')
            ->join('exception_types as et', 'et.id', '=', 'er.exception_type_id')
            ->leftJoin('users as au', 'au.id', '=', 'er.approver_id')
            ->leftJoin('user_profiles as aup', 'aup.user_id', '=', 'au.id')
            ->where('er.company_id', $companyId)
            ->whereNull('er.deleted_at');
    }

    private function selectColumns()
    {
        return [
            'er.id',
            'et.name as type_name',
            'er.reason',
            'er.request_date',
            'er.start_time',
            'er.end_time',
            'er.duration_minutes',
            'er.attachment',
            'er.status',
            'eu.id as employee_id',
            'eup.full_name as employee_name',
            'b.id as branch_id',
            'b.name as branch_name',
            'd.name as department_name',
            'au.id as approver_id',
            'aup.full_name as approver_name',
            'er.approved_at',
            'er.rejection_reason',
            'er.created_at',
        ];
    }

    public function list(int $companyId, array $filters = []): array
    {
        $query = $this->baseQuery($companyId)->select($this->selectColumns());

        if (!empty($filters['status'])) {
            $query->where('er.status', $filters['status']);
        } else {
            $query->where('er.status', '!=', 'cancelled');
        }

        if (!empty($filters['type'])) {
            $query->where('et.slug', $filters['type']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('b.id', $filters['branch_id']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('er.created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('er.created_at', '<=', $filters['to']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);
        $page = (int) ($filters['page'] ?? 1);

        $total = (clone $query)->count();

        $items = $query->orderByDesc('er.created_at')
            ->forPage($page, $perPage)
            ->get()
            ->all();

        return [
            'data' => $items,
            'meta' => [
                'total' => $total,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
                'per_page' => $perPage,
            ],
        ];
    }

    public function find(int $id, int $companyId): ?object
    {
        return $this->baseQuery($companyId)
            ->where('er.id', $id)
            ->select($this->selectColumns())
            ->first();
    }

    public function updateStatus(int $id, array $data): void
    {
        DB::table('exception_requests')->where('id', $id)->update(array_merge($data, [
            'updated_at' => now(),
        ]));
    }

    public function pendingCount(int $companyId): int
    {
        return DB::table('exception_requests')
            ->where('company_id', $companyId)
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->count();
    }
}