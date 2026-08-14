<?php

namespace App\Services\Owner;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollReportService
{
    public function getData(int $companyId, array $filters): array
    {
        $month = $filters['month'] ?? now()->month;
        $year = $filters['year'] ?? now()->year;

        $period = DB::table('payroll_periods')
            ->where('company_id', $companyId)
            ->where('month', $month)
            ->where('year', $year)
            ->whereNull('deleted_at')
            ->first();

        if (!$period) {
            return [
                'period' => ['month' => (int) $month, 'year' => (int) $year],
                'summary' => [
                    'total_gross' => 0,
                    'total_deductions' => 0,
                    'total_bonuses' => 0,
                    'total_net' => 0,
                    'employees_count' => 0,
                ],
                'chart' => [],
                'records' => [],
            ];
        }

        $query = DB::table('payroll_records as pr')
            ->join('users as u', 'u.id', '=', 'pr.employee_user_id')
            ->join('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->leftJoin('employee_details as ed', 'ed.user_id', '=', 'u.id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.department_id')
            ->leftJoin('branches as b', 'b.id', '=', 'd.branch_id')
            ->where('pr.period_id', $period->id)
            ->whereNull('pr.deleted_at');

        if (!empty($filters['branch_id'])) {
            $query->where('b.id', $filters['branch_id']);
        }

        $records = $query->select([
            'p.full_name as employee_name',
            'b.name as branch_name',
            'pr.gross_salary',
            'pr.total_deductions',
            'pr.total_bonuses',
            'pr.net_salary',
            'pr.status',
        ])->get();

        $chartData = (clone $query)
            ->select('b.name', DB::raw('SUM(pr.net_salary) as total'))
            ->groupBy('b.name')
            ->pluck('total', 'name')
            ->map(fn($v) => (float) $v)
            ->toArray();

        return [
            'period' => ['month' => (int) $period->month, 'year' => (int) $period->year, 'status' => $period->status],
            'summary' => [
                'total_gross' => (float) $records->sum('gross_salary'),
                'total_deductions' => (float) $records->sum('total_deductions'),
                'total_bonuses' => (float) $records->sum('total_bonuses'),
                'total_net' => (float) $records->sum('net_salary'),
                'employees_count' => $records->count(),
            ],
            'chart' => $chartData,
            'records' => $records->map(fn($r) => [
                'employee_name' => $r->employee_name,
                'branch_name' => $r->branch_name ?? 'N/A',
                'gross_salary' => (float) $r->gross_salary,
                'total_deductions' => (float) $r->total_deductions,
                'total_bonuses' => (float) $r->total_bonuses,
                'net_salary' => (float) $r->net_salary,
                'status' => $r->status,
            ])->all(),
        ];
    }
}