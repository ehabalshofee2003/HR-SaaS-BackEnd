<?php

namespace App\Repositories\Hr;

use App\Models\Hr\PerformanceEvaluation;

class EvaluationRepository
{
    public function getEmployeeEvaluations(int $userId, int $companyId)
    {
        return PerformanceEvaluation::where('employee_user_id', $userId)
            ->where('company_id', $companyId)
            ->where('status', 'completed') // الموظف يرى التقييمات المكتملة فقط عادة
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
        // نحدث فقط إذا لم يكن قد قرأها مسبقاً لتجنب استعلامات فارغة
        if (is_null($evaluation->read_at)) {
            return $evaluation->update(['read_at' => now()]);
        }
        return true;
    }
}