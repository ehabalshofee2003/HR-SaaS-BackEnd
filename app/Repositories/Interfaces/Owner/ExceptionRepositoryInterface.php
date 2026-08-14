<?php

namespace App\Repositories\Interfaces\Owner;

interface ExceptionRepositoryInterface
{
    public function list(int $companyId, array $filters = []): array;
    public function find(int $id, int $companyId): ?object;
    public function updateStatus(int $id, array $data): void;
    public function pendingCount(int $companyId): int;
}