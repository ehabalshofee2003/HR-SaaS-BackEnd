<?php

namespace App\Services\Owner;

use Illuminate\Support\Facades\DB;

class FinancialReportService
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
                    'total_base_salary' => 0,
                    'total_allowances' => 0,
                    'total_bonuses' => 0,
                    'total_overtime' => 0,
                    'total_deductions' => 0,
                    'net_total' => 0,
                ],
                'chart' => [],
                'records' => [],
            ];
        }

        $recordIds = DB::table('payroll_records')
            ->where('period_id', $period->id)
            ->whereNull('deleted_at')
            ->pluck('id');

        $details = DB::table('payroll_record_details')
            ->whereIn('record_id', $recordIds)
            ->get();

        $byType = $details->groupBy('component_type')->map(fn($g) => (float) $g->sum('amount'));

        $totalBase = $byType->get('base_salary', 0);
        $totalAllowances = $byType->get('allowance', 0);
        $totalBonuses = $byType->get('bonus', 0);
        $totalOvertime = $byType->get('overtime', 0);
        $totalDeductions = $byType->get('deduction', 0);

        $records = DB::table('payroll_records as pr')
            ->join('users as u', 'u.id', '=', 'pr.employee_user_id')
            ->join('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->where('pr.period_id', $period->id)
            ->whereNull('pr.deleted_at')
            ->select(['pr.id', 'p.full_name as employee_name', 'pr.gross_salary', 'pr.total_deductions', 'pr.total_bonuses', 'pr.net_salary'])
            ->get()
            ->map(function ($r) use ($details) {
                $recordDetails = $details->where('record_id', $r->id);

                return [
                    'employee_name' => $r->employee_name,
                    'base_salary' => (float) $recordDetails->where('component_type', 'base_salary')->sum('amount'),
                    'allowances' => (float) $recordDetails->where('component_type', 'allowance')->sum('amount'),
                    'bonuses' => (float) $recordDetails->where('component_type', 'bonus')->sum('amount'),
                    'overtime' => (float) $recordDetails->where('component_type', 'overtime')->sum('amount'),
                    'deductions' => (float) $recordDetails->where('component_type', 'deduction')->sum('amount'),
                    'net_salary' => (float) $r->net_salary,
                ];
            });

        return [
            'period' => ['month' => (int) $period->month, 'year' => (int) $period->year, 'status' => $period->status],
            'summary' => [
                'total_base_salary' => $totalBase,
                'total_allowances' => $totalAllowances,
                'total_bonuses' => $totalBonuses,
                'total_overtime' => $totalOvertime,
                'total_deductions' => $totalDeductions,
                'net_total' => $totalBase + $totalAllowances + $totalBonuses + $totalOvertime - $totalDeductions,
            ],
            'chart' => [
                'الأساسي' => $totalBase,
                'البدلات' => $totalAllowances,
                'المكافآت' => $totalBonuses,
                'الأوفرتايم' => $totalOvertime,
                'الاستقطاعات' => $totalDeductions,
            ],
            'records' => $records->all(),
        ];
    }
}