<?php

namespace App\Services\SuperAdmin;

use App\Repositories\Interfaces\SuperAdmin\CompanyRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompanyService
{
    public function __construct(
        private CompanyRepositoryInterface $repository,
    ) {}

    public function list(array $filters): array
    {
        $result = $this->repository->list($filters);

        return [
            'data' => array_map(fn($c) => $this->formatListItem($c), $result['data']),
            'meta' => $result['meta'],
        ];
    }

    public function get(int $id): array
    {
        $company = $this->repository->find($id);

        if (!$company) {
            throw ValidationException::withMessages(['company' => ['Company not found.']]);
        }

        return $this->formatDetails($company);
    }

    public function create(array $data): array
    {
        if ($this->repository->emailOrPhoneExists($data['phone'], $data['email'] ?? null)) {
            throw ValidationException::withMessages(['phone' => ['Phone or email already exists.']]);
        }

        $ownerId = $this->repository->createOwner([
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password_hash' => Hash::make(Str::random(32)),
        ]);

        $companyId = $this->repository->createCompany([
            'owner_user_id' => $ownerId,
            'owner_name' => $data['owner_name'],
            'name' => $data['name'],
            'industry' => $data['industry'] ?? null,
            'website' => $data['website'] ?? null,
        ]);

        if (!empty($data['plan_id'])) {
            $this->repository->createSubscription([
                'company_id' => $companyId,
                'plan_id' => $data['plan_id'],
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
            ]);
        }

        return $this->get($companyId);
    }

    public function update(int $id, array $data): array
    {
        $company = $this->repository->find($id);

        if (!$company) {
            throw ValidationException::withMessages(['company' => ['Company not found.']]);
        }

        $this->repository->update($id, array_filter([
            'name' => $data['name'] ?? null,
            'industry' => $data['industry'] ?? null,
            'website' => $data['website'] ?? null,
        ], fn($v) => $v !== null));

        return $this->get($id);
    }

    public function suspend(int $id): array
    {
        $this->repository->update($id, ['status' => 'suspended']);

        return ['success' => true, 'message' => 'تم تعليق الشركة بنجاح.'];
    }

    public function activate(int $id): array
    {
        $this->repository->update($id, ['status' => 'active']);

        return ['success' => true, 'message' => 'تم تفعيل الشركة بنجاح.'];
    }

    public function delete(int $id): array
    {
        $company = $this->repository->find($id);

        if (!$company) {
            throw ValidationException::withMessages(['company' => ['Company not found.']]);
        }

        $this->repository->softDelete($id);

        return ['success' => true, 'message' => 'تم حذف الشركة.'];
    }

    private function formatListItem(object $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'owner_name' => $c->owner_name,
            'owner_email' => $c->owner_email,
            'owner_phone' => $c->owner_phone,
            'plan_name' => $c->plan_name,
            'status' => $c->status,
            'branches_count' => $this->repository->branchesCount($c->id),
            'employees_count' => $this->repository->employeesCount($c->id),
            'created_at' => Carbon::parse($c->created_at)->toDateString(),
        ];
    }

    private function formatDetails(object $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'status' => $c->status,
            'industry' => $c->industry,
            'website' => $c->website,
            'owner' => ['id' => $c->owner_id, 'name' => $c->owner_name, 'email' => $c->owner_email, 'phone' => $c->owner_phone],
            'subscription' => $c->plan_name ? [
                'plan_name' => $c->plan_name,
                'start_date' => $c->start_date,
                'end_date' => $c->end_date,
                'status' => $c->subscription_status,
            ] : null,
            'branches_count' => $this->repository->branchesCount($c->id),
            'employees_count' => $this->repository->employeesCount($c->id),
            'created_at' => Carbon::parse($c->created_at)->toDateString(),
        ];
    }
}