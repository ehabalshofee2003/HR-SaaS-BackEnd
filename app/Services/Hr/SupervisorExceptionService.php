<?php

namespace App\Services\Hr;

use App\Repositories\Hr\SupervisorExceptionRepository;
use Exception;

class SupervisorExceptionService
{
    public function __construct(
        protected SupervisorExceptionRepository $repository
    ) {}

    public function list(int $supervisorId, array $filters): array
    {
        return $this->repository->listForSupervisor($supervisorId, $filters);
    }

    public function get(int $id, int $supervisorId): object
    {
        $record = $this->repository->findForSupervisor($id, $supervisorId);
        if (!$record) throw new Exception('Exception request not found.', 404);
        return $record;
    }

    public function forwardToOwner(int $id, int $supervisorId): object
    {
        $record = $this->repository->findForSupervisor($id, $supervisorId);
        if (!$record) throw new Exception('Exception request not found.', 404);
        if ($record->status !== 'pending') throw new Exception('This request has already been reviewed.');

        $this->repository->updateStatus($id, ['status' => 'pending_manager']);
        return $this->repository->findForSupervisor($id, $supervisorId);
    }

    public function reject(int $id, int $supervisorId, string $reason): object
    {
        $record = $this->repository->findForSupervisor($id, $supervisorId);
        if (!$record) throw new Exception('Exception request not found.', 404);
        if ($record->status !== 'pending') throw new Exception('This request has already been reviewed.');

        $this->repository->updateStatus($id, ['status' => 'rejected', 'rejection_reason' => $reason]);
        return $this->repository->findForSupervisor($id, $supervisorId);
    }
}