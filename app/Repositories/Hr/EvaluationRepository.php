<?php

namespace App\Repositories\Hr;

use App\Models\Hr\PerformanceEvaluation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EvaluationRepository
{
    // ================= دوال Branch Manager (جديدة) =================

    public function paginateForBranch(int $branchId, array $filters, int $perPage = 15)
    {
        $query = DB::table('performance_evaluations')
            ->join('employee_details', 'performance_evaluations.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->join('user_profiles as employee_profiles', 'performance_evaluations.employee_user_id', '=', DB::raw('employee_profiles.user_id'))
            ->leftJoin('user_profiles as supervisor_profiles', 'performance_evaluations.supervisor_user_id', '=', DB::raw('supervisor_profiles.user_id'))
            ->where('departments.branch_id', $branchId)
            ->whereNull('performance_evaluations.deleted_at')
            ->select(
                'performance_evaluations.id',
                'performance_evaluations.overall_score',
                'performance_evaluations.status',
                'performance_evaluations.period_start',
                'performance_evaluations.period_end',
                'performance_evaluations.created_at',
                'employee_profiles.full_name as employee_name',
                'supervisor_profiles.full_name as evaluator_name',
                'employee_details.department_id',
                'departments.name as department_name'
            );

        if (!empty($filters['status'])) {
            $query->where('performance_evaluations.status', $filters['status']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('employee_details.department_id', $filters['department_id']);
        }

        if (!empty($filters['employee_id'])) {
            $query->where('performance_evaluations.employee_user_id', $filters['employee_id']);
        }

        if (!empty($filters['supervisor_id'])) {
            $query->where('performance_evaluations.supervisor_user_id', $filters['supervisor_id']);
        }

        return $query->orderByDesc('performance_evaluations.created_at')->paginate($perPage);
    }

    public function findForBranch(int $id, int $branchId): ?object
    {
        return DB::table('performance_evaluations')
            ->join('employee_details', 'performance_evaluations.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('performance_evaluations.id', $id)
            ->where('departments.branch_id', $branchId)
            ->whereNull('performance_evaluations.deleted_at')
            ->select('performance_evaluations.*')
            ->first();
    }

    public function employeeInBranch(int $employeeUserId, int $branchId): ?object
    {
        return DB::table('employee_details')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('employee_details.user_id', $employeeUserId)
            ->where('departments.branch_id', $branchId)
            ->whereNull('employee_details.deleted_at')
            ->select('employee_details.*')
            ->first();
    }

    public function getActiveCriteriaForCompany(int $companyId): array
    {
        return DB::table('evaluation_criteria')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get()
            ->toArray();
    }

    public function createEvaluation(array $data): int
    {
        return DB::table('performance_evaluations')->insertGetId([
            'company_id' => $data['company_id'],
            'employee_user_id' => $data['employee_user_id'],
            'supervisor_user_id' => $data['supervisor_user_id'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'overall_score' => $data['overall_score'],
            'notes' => $data['notes'] ?? null,
            'status' => 'submitted',
            'submitted_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function insertScore(int $evaluationId, int $criteriaId, float $score, ?string $comments): void
    {
        DB::table('evaluation_scores')->insert([
            'evaluation_id' => $evaluationId,
            'criteria_id' => $criteriaId,
            'score' => $score,
            'comments' => $comments,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function getScoresForEvaluation(int $evaluationId): array
    {
        return DB::table('evaluation_scores')
            ->join('evaluation_criteria', 'evaluation_scores.criteria_id', '=', 'evaluation_criteria.id')
            ->where('evaluation_scores.evaluation_id', $evaluationId)
            ->select(
                'evaluation_scores.id',
                'evaluation_scores.score',
                'evaluation_scores.comments',
                'evaluation_criteria.name as criteria_name'
            )
            ->get()
            ->toArray();
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

    public function getEmployeeEvaluations(int $userId, int $companyId)
    {
        return PerformanceEvaluation::where('employee_user_id', $userId)
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->latest('period_end')
            ->paginate(15);
    }

    public function findEmployeeEvaluation(int $id, int $userId, int $companyId): ?PerformanceEvaluation
    {
        return PerformanceEvaluation::where('id', $id)
            ->where('employee_user_id', $userId)
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->first();
    }

    public function markAsRead(PerformanceEvaluation $evaluation): bool
    {
        if (is_null($evaluation->read_at)) {
            return $evaluation->update(['read_at' => now()]);
        }
        return true;
    }
}