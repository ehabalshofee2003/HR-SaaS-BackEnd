<?php

namespace App\Services\Hr;

use App\Repositories\Hr\EvaluationRepository;
use App\Models\Identity\User;
use Illuminate\Support\Facades\DB;
use Exception;

class EvaluationService
{
    public function __construct(
        private EvaluationRepository $repository
    ) {}

    // ================= دوال Branch Manager (جديدة) =================

    public function list(User $manager, array $filters)
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->repository->paginateForBranch($branchId, $filters);
    }

    public function getDetails(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $evaluation = $this->repository->findForBranch($id, $branchId);

        if (!$evaluation) {
            throw new Exception('التقييم غير موجود.', 404);
        }

        $evaluation->scores = $this->repository->getScoresForEvaluation($id);

        return $evaluation;
    }

    public function evaluate(User $manager, array $data): object
    {
        $branchId = $manager->getCurrentBranchId();
        $companyId = $manager->getCurrentCompanyId();

        $employeeDetail = $this->repository->employeeInBranch($data['employee_user_id'], $branchId);
        if (!$employeeDetail) {
            throw new Exception('الموظف المحدد لا ينتمي لهذا الفرع.');
        }

        $criteria = $this->repository->getActiveCriteriaForCompany($companyId);
        $validCriteriaIds = array_column($criteria, 'id');

        foreach ($data['scores'] as $scoreItem) {
            if (!in_array($scoreItem['criteria_id'], $validCriteriaIds)) {
                throw new Exception('أحد معايير التقييم المرسلة غير صالح لهذه الشركة.');
            }
        }

        $averageScore = round(
            array_sum(array_column($data['scores'], 'score')) / count($data['scores']),
            2
        );

        return DB::transaction(function () use ($data, $companyId, $manager, $averageScore) {
            $evaluationId = $this->repository->createEvaluation([
                'company_id' => $companyId,
                'employee_user_id' => $data['employee_user_id'],
                'supervisor_user_id' => $manager->id,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'overall_score' => $averageScore,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['scores'] as $scoreItem) {
                $this->repository->insertScore(
                    $evaluationId,
                    $scoreItem['criteria_id'],
                    $scoreItem['score'],
                    $scoreItem['comments'] ?? null
                );
            }

            // TODO: إشعار الموظف بالتقييم الجديد

            $evaluation = $this->repository->findForBranch($evaluationId, $manager->getCurrentBranchId());
            $evaluation->scores = $this->repository->getScoresForEvaluation($evaluationId);

            return $evaluation;
        });
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

    public function getAll($user)
    {
        $companyId = $user->getCurrentCompanyId();
        return $this->repository->getEmployeeEvaluations($user->id, $companyId);
    }

    public function getById($user, $id)
    {
        $companyId = $user->getCurrentCompanyId();
        return $this->repository->findEmployeeEvaluation((int)$id, $user->id, $companyId);
    }

    public function markAsRead($user, $id)
    {
        $companyId = $user->getCurrentCompanyId();
        $evaluation = $this->repository->findEmployeeEvaluation((int)$id, $user->id, $companyId);

        if (!$evaluation) {
            return null;
        }

        $this->repository->markAsRead($evaluation);
        return $evaluation->refresh();
    }
}