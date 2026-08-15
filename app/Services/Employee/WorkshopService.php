<?php

namespace App\Services\Employee;

use App\Repositories\Employee\WorkshopRepository;
use App\Models\Identity\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class WorkshopService
{
    public function __construct(
        private WorkshopRepository $repository,
    ) {}

    public function list(int $employeeUserId): array
    {
        $branchId = User::find($employeeUserId)?->branch_id;

        if (!$branchId) {
            return [];
        }

        $workshops = $this->repository->listForEmployee($employeeUserId, $branchId);

        return array_map(fn($w) => [
            'id' => $w->id,
            'title' => $w->title,
            'start_date' => Carbon::parse($w->start_date)->toIso8601String(),
            'end_date' => Carbon::parse($w->end_date)->toIso8601String(),
            'location' => $w->location,
            'responsible_name' => $w->responsible_name,
            'capacity' => $w->capacity,
            'seats_available' => max(0, $w->capacity - $w->registered_count),
            'is_registered' => $this->repository->isRegistered($w->id, $employeeUserId),
        ], $workshops);
    }

    public function register(int $workshopId, int $employeeUserId): array
    {
        $workshop = $this->repository->find($workshopId);

        if (!$workshop) {
            throw ValidationException::withMessages(['workshop' => ['Workshop not found.']]);
        }

        if ($this->repository->isRegistered($workshopId, $employeeUserId)) {
            throw ValidationException::withMessages(['workshop' => ['Already registered.']]);
        }

        if ($this->repository->registeredCount($workshopId) >= $workshop->capacity) {
            throw ValidationException::withMessages(['workshop' => ['Workshop is full.']]);
        }

        if (Carbon::parse($workshop->start_date)->isPast()) {
            throw ValidationException::withMessages(['workshop' => ['Registration closed, workshop already started.']]);
        }

        $this->repository->register($workshopId, $employeeUserId);

        return ['success' => true, 'message' => 'Registered successfully.'];
    }

    public function cancel(int $workshopId, int $employeeUserId): array
    {
        $workshop = $this->repository->find($workshopId);

        if (!$workshop) {
            throw ValidationException::withMessages(['workshop' => ['Workshop not found.']]);
        }

        if (!$this->repository->isRegistered($workshopId, $employeeUserId)) {
            throw ValidationException::withMessages(['workshop' => ['You are not registered in this workshop.']]);
        }

        if (Carbon::parse($workshop->start_date)->isPast()) {
            throw ValidationException::withMessages(['workshop' => ['Cannot cancel after the workshop has started.']]);
        }

        $this->repository->cancel($workshopId, $employeeUserId);

        return ['success' => true, 'message' => 'Registration cancelled.'];
    }
}