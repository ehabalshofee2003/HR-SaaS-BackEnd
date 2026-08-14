<?php

namespace App\Repositories\Interfaces\Supervisor;

interface TaskManagementRepositoryInterface
{
    public function list(int $supervisorId, array $filters): array;
    public function find(int $id, int $supervisorId): ?object;
    public function create(array $data): int;
    public function update(int $id, array $data): void;
    public function delete(int $id): void;
}