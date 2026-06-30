<?php

namespace App\Services\Hr;

use App\Repositories\Hr\WorkshopRepository;
use Illuminate\Support\Facades\Auth;

class WorkshopService
{
    public function __construct(
        private WorkshopRepository $repository
    ) {}

    private function getUserContext($user)
    {
        $companyId = $user->getCurrentCompanyId();
        $branchId = $user->employeeDetail?->department?->branch_id;
        return [$companyId, $branchId];
    }
    
    public function getAll($user)
    {
        [$companyId, $branchId] = $this->getUserContext($user);
        return $this->repository->getAvailableWorkshops($companyId, $branchId);
    }

    // أضف هذه الدالة الجديدة لكي نستدعيها من الكنترولر
    public function getRegisteredWorkshopIds($user)
    {
        return $this->repository->getRegisteredWorkshopIds($user->id);
    }

    public function getById($user, $id)
    {
        [$companyId, $branchId] = $this->getUserContext($user);
        return $this->repository->findWorkshop((int)$id, $companyId, $branchId);
    }

    public function getMyWorkshops($user)
    {
        return $this->repository->getMyWorkshops($user->id);
    }

    public function getMyWorkshopById($user, $id)
    {
        return $this->repository->getMyWorkshopById((int)$id, $user->id);
    }

    public function register($user, $id)
    {
        [$companyId, $branchId] = $this->getUserContext($user);
        $workshop = $this->repository->findWorkshop((int)$id, $companyId, $branchId);

        if (!$workshop || $workshop->status !== 'upcoming') {
            return ['error' => 'Workshop not found or cannot be registered.', 'code' => 404];
        }

        // التحقق من السعة
        if ($workshop->capacity > 0) {
            $currentCount = $this->repository->getRegisteredCount($workshop->id);
            if ($currentCount >= $workshop->capacity) {
                return ['error' => 'Workshop has reached its maximum capacity.', 'code' => 422];
            }
        }

        // التحقق مما إذا كان مسجل مسبقاً (تجنب تكرار المحاولة رغم وجود Unique Constraint في الداتا)
        if ($this->repository->findAttendee($workshop->id, $user->id)) {
            return ['error' => 'You are already registered in this workshop.', 'code' => 409];
        }

        return $this->repository->register($workshop->id, $user->id);
    }

    public function unregister($user, $id)
    {
        [$companyId, $branchId] = $this->getUserContext($user);
        $workshop = $this->repository->findWorkshop((int)$id, $companyId, $branchId);

        if (!$workshop) {
            return ['error' => 'Workshop not found.', 'code' => 404];
        }

        $attendee = $this->repository->findAttendee($workshop->id, $user->id);

        if (!$attendee) {
            return ['error' => 'You are not registered in this workshop or already cancelled.', 'code' => 404];
        }

        $this->repository->unregister($attendee);
        return true;
    }
}