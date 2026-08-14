<?php

namespace App\Services\Supervisor;

use App\Repositories\Interfaces\Supervisor\LeaveRepositoryInterface;
use App\Repositories\Interfaces\Supervisor\EmployeeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function __construct(
        private LeaveRepositoryInterface $leaveRepository,
        private EmployeeRepositoryInterface $employeeRepository,
    ) {}

    public function getForEmployee(int $employeeId, int $supervisorId): array
    {
        $employee = $this->employeeRepository->find($employeeId, $supervisorId);

        if (!$employee) {
            throw ValidationException::withMessages(['employee' => ['Employee not found.']]);
        }

        $balances = $this->leaveRepository->balances($employeeId);
        $history = $this->leaveRepository->history($employeeId);

        return [
            'balances' => array_map(fn($b) => [
                'type' => $b->type_name,
                'total_days' => (float) $b->total_days,
                'remaining_days' => (float) $b->remaining_days,
            ], $balances),
            'history' => array_map(fn($h) => [
                'type' => $h->type_name,
                'start_date' => $h->start_date,
                'end_date' => $h->end_date,
                'status' => $h->status,
                'reason' => $h->reason,
            ], $history),
        ];
    }
}