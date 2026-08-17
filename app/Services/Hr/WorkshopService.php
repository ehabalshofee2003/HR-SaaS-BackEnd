<?php

namespace App\Services\Hr;

use App\Repositories\Hr\WorkshopRepository;
use App\Models\Identity\User;
use Exception;

class WorkshopService
{
    public function __construct(
        private WorkshopRepository $repository
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
        $workshop = $this->repository->findForBranch($id, $branchId);

        if (!$workshop) {
            throw new Exception('الورشة غير موجودة.', 404);
        }

        return $workshop;
    }

    public function create(User $manager, array $data): object
    {
        $branchId = $manager->getCurrentBranchId();
        $companyId = $manager->getCurrentCompanyId();

        $id = $this->repository->create([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'created_by' => $manager->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => $data['location'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'capacity' => $data['capacity'],
        ]);

        // TODO: إشعار الموظفين المستهدفين بالورشة الجديدة

        return $this->repository->findForBranch($id, $branchId);
    }

    public function update(int $id, array $data, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $workshop = $this->repository->findForBranch($id, $branchId);

        if (!$workshop) {
            throw new Exception('الورشة غير موجودة.', 404);
        }

        if ($workshop->status !== 'upcoming') {
            throw new Exception('لا يمكن تعديل ورشة إلا إذا كانت قادمة (Upcoming).');
        }

        $this->repository->update($id, $data);

        return $this->repository->findForBranch($id, $branchId);
    }

    public function cancel(int $id, User $manager, string $reason): object
    {
        $branchId = $manager->getCurrentBranchId();
        $workshop = $this->repository->findForBranch($id, $branchId);

        if (!$workshop) {
            throw new Exception('الورشة غير موجودة.', 404);
        }

        if ($workshop->status !== 'upcoming') {
            throw new Exception('لا يمكن إلغاء ورشة إلا إذا كانت قادمة (Upcoming).');
        }

        $this->repository->update($id, ['status' => 'cancelled']);
        $this->repository->cancelAllRegistrations($id);

        // TODO: إشعار كل المسجَّلين بالإلغاء (reason: $reason)

        return $this->repository->findForBranch($id, $branchId);
    }

    public function getAttendees(int $id, User $manager): array
    {
        $branchId = $manager->getCurrentBranchId();
        $workshop = $this->repository->findForBranch($id, $branchId);

        if (!$workshop) {
            throw new Exception('الورشة غير موجودة.', 404);
        }

        return $this->repository->getAttendeesForWorkshop($id);
    }

    public function markAttendance(int $workshopId, int $employeeUserId, User $manager): void
    {
        $branchId = $manager->getCurrentBranchId();
        $workshop = $this->repository->findForBranch($workshopId, $branchId);

        if (!$workshop) {
            throw new Exception('الورشة غير موجودة.', 404);
        }

        $attendee = $this->repository->findAttendeeRecord($workshopId, $employeeUserId);

        if (!$attendee || $attendee->status !== 'registered') {
            throw new Exception('الموظف غير مسجَّل بهذه الورشة أصلاً.', 404);
        }

        $this->repository->updateAttendeeStatus($attendee->id, 'attended');
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================
private function getUserContext($user)
{
    $companyId = $user->getCurrentCompanyId();
    $branchId = $user->getCurrentBranchId();
    return [$companyId, $branchId];
}

    public function getAll($user)
    {
        [$companyId, $branchId] = $this->getUserContext($user);
        return $this->repository->getAvailableWorkshops($companyId, $branchId);
    }

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

        if ($workshop->capacity > 0) {
            $currentCount = $this->repository->getRegisteredCount($workshop->id);
            if ($currentCount >= $workshop->capacity) {
                return ['error' => 'Workshop has reached its maximum capacity.', 'code' => 422];
            }
        }

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