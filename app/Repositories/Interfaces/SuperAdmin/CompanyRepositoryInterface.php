<?php

namespace App\Repositories\Interfaces\SuperAdmin;

interface CompanyRepositoryInterface
{
    public function list(array $filters): array;
    public function find(int $id): ?object;
    public function emailOrPhoneExists(string $phone, ?string $email): bool;
    public function createOwner(array $data): int;
    public function createCompany(array $data): int;
    public function createSubscription(array $data): void;
    public function update(int $id, array $data): void;
    public function softDelete(int $id): void;
    public function branchesCount(int $companyId): int;
    public function employeesCount(int $companyId): int;
}