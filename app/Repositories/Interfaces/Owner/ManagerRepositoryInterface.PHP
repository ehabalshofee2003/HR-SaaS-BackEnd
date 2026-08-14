<?php

namespace App\Repositories\Interfaces\Owner;

interface ManagerRepositoryInterface
{
    public function list(int $companyId, array $filters = []): array;
    public function find(int $id, int $companyId): ?object;
    public function phoneExists(string $phone, ?int $excludeId = null): bool;
    public function emailExists(string $email, ?int $excludeId = null): bool;
    public function branchHasManager(int $branchId, ?int $excludeId = null): bool;
    public function branchBelongsToCompany(int $branchId, int $companyId): bool;
    public function createUser(array $data): int;
    public function createProfile(array $data): void;
    public function updateUser(int $id, array $data): void;
    public function updateProfile(int $id, array $data): void;
    public function softDelete(int $id): void;
}