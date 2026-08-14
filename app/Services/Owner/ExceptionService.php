<?php

namespace App\Services\Owner;

use App\Repositories\Interfaces\Owner\ExceptionRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ExceptionService
{
    public function __construct(
        private ExceptionRepositoryInterface $repository,
    ) {}

    public function list(int $companyId, array $filters = []): array
    {
        $result = $this->repository->list($companyId, $filters);

        return [
            'data' => array_map(fn($e) => $this->format($e), $result['data']),
            'meta' => $result['meta'],
        ];
    }

    public function get(int $id, int $companyId): array
    {
        $exception = $this->repository->find($id, $companyId);

        if (!$exception) {
            throw ValidationException::withMessages(['exception' => ['Exception request not found.']]);
        }

        return $this->format($exception);
    }

    public function decide(int $id, int $companyId, int $ownerId, array $data): array
    {
        $exception = $this->repository->find($id, $companyId);

        if (!$exception) {
            throw ValidationException::withMessages(['exception' => ['Exception request not found.']]);
        }

        if ($exception->status !== 'pending') {
            throw ValidationException::withMessages(['status' => ['This exception request has already been decided.']]);
        }

        if ($data['status'] === 'rejected' && empty($data['rejection_reason'])) {
            throw ValidationException::withMessages(['rejection_reason' => ['Rejection reason is required when rejecting.']]);
        }

        $this->repository->updateStatus($id, [
            'status' => $data['status'],
            'approver_id' => $ownerId,
            'approved_at' => now(),
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        return [
            'success' => true,
            'message' => 'Exception request ' . $data['status'] . ' successfully.',
            'data' => [
                'id' => $id,
                'status' => $data['status'],
                'approved_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function pendingCount(int $companyId): array
    {
        return ['pending_count' => $this->repository->pendingCount($companyId)];
    }

    private function format(object $e): array
    {
        return [
            'id' => $e->id,
            'type' => $e->type_name,
            'reason' => $e->reason,
            'request_date' => $e->request_date,
            'start_time' => $e->start_time,
            'end_time' => $e->end_time,
            'duration_minutes' => (int) $e->duration_minutes,
            'attachment' => $e->attachment ? Storage::url($e->attachment) : null,
            'status' => $e->status,
            'employee' => [
                'id' => $e->employee_id,
                'name' => $e->employee_name,
                'branch_name' => $e->branch_name,
                'department_name' => $e->department_name,
            ],
            'approved_by' => $e->approver_id ? [
                'id' => $e->approver_id,
                'name' => $e->approver_name,
            ] : null,
            'approved_at' => $e->approved_at ? Carbon::parse($e->approved_at)->toIso8601String() : null,
            'rejection_reason' => $e->rejection_reason,
            'created_at' => Carbon::parse($e->created_at)->toIso8601String(),
        ];
    }
}