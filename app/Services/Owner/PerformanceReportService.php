<?php

namespace App\Services\Owner;

use Illuminate\Support\Facades\DB;

class PerformanceReportService
{
    public function getData(int $companyId, array $filters): array
    {
        $query = DB::table('performance_evaluations as pe')
            ->join('users as eu', 'eu.id', '=', 'pe.employee_user_id')
            ->join('user_profiles as ep', 'ep.user_id', '=', 'eu.id')
            ->join('users as su', 'su.id', '=', 'pe.supervisor_user_id')
            ->join('user_profiles as sp', 'sp.user_id', '=', 'su.id')
            ->leftJoin('employee_details as ed', 'ed.user_id', '=', 'eu.id')
            ->leftJoin('departments as d', 'd.id', '=', 'ed.department_id')
            ->leftJoin('branches as b', 'b.id', '=', 'd.branch_id')
            ->where('pe.company_id', $companyId)
            ->where('pe.status', 'reviewed')
            ->whereNull('pe.deleted_at');

        if (!empty($filters['branch_id'])) {
            $query->where('b.id', $filters['branch_id']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('pe.period_start', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('pe.period_end', '<=', $filters['to']);
        }

        $records = $query->select([
            'ep.full_name as employee_name',
            'b.name as branch_name',
            'sp.full_name as supervisor_name',
            'pe.period_start',
            'pe.period_end',
            'pe.overall_score',
            'pe.notes',
        ])->orderByDesc('pe.overall_score')->get();

        $avgScore = $records->count() > 0 ? round($records->avg('overall_score'), 2) : 0;
        $topScore = $records->max('overall_score') ?? 0;
        $lowScore = $records->count() > 0 ? $records->min('overall_score') : 0;

        // توزيع حسب فئات الأداء
        $distribution = [
            'ممتاز (90+)' => $records->where('overall_score', '>=', 90)->count(),
            'جيد جدًا (75-89)' => $records->whereBetween('overall_score', [75, 89.99])->count(),
            'متوسط (60-74)' => $records->whereBetween('overall_score', [60, 74.99])->count(),
            'ضعيف (أقل من 60)' => $records->where('overall_score', '<', 60)->count(),
        ];

        return [
            'summary' => [
                'total_evaluations' => $records->count(),
                'average_score' => $avgScore,
                'top_score' => (float) $topScore,
                'lowest_score' => (float) $lowScore,
            ],
            'chart' => $distribution,
            'records' => $records->map(fn($r) => [
                'employee_name' => $r->employee_name,
                'branch_name' => $r->branch_name ?? 'N/A',
                'supervisor_name' => $r->supervisor_name,
                'period_start' => $r->period_start,
                'period_end' => $r->period_end,
                'overall_score' => (float) $r->overall_score,
                'notes' => $r->notes,
            ])->all(),
        ];
    }
}