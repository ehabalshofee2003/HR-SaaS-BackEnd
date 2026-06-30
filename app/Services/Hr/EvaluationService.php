<?php

namespace App\Services\Hr;

use App\Repositories\Hr\EvaluationRepository;

class EvaluationService
{
    public function __construct(
        private EvaluationRepository $repository
    ) {}

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