<?php

namespace App\Services\Owner;

use App\Repositories\Owner\BranchRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class BranchService
{
    public function __construct(
        protected BranchRepository $branchRepository
    ) {}

    public function list(int $ownerId, array $filters): array
    {
        $companyId = $this->branchRepository->getCompanyIdForOwner($ownerId);
        $branches = $this->branchRepository->listForCompany($companyId, $filters);

        return $branches->map(fn($branch) => $this->formatBranch($branch))->all();
    }

    public function create(int $ownerId, array $data): array
    {
        $companyId = $this->branchRepository->getCompanyIdForOwner($ownerId);

        if ($this->branchRepository->nameExists($companyId, $data['name'])) {
            throw new Exception('يوجد فرع بنفس هذا الاسم بالفعل بشركتك.', 422);
        }

        if (!empty($data['manager_user_id'])) {
            $this->validateManager($data['manager_user_id']);
        }

        return DB::transaction(function () use ($companyId, $data) {
            $branchId = $this->branchRepository->create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'location' => $data['location'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            if (!empty($data['manager_user_id'])) {
                $this->branchRepository->assignManagerToBranch($data['manager_user_id'], $branchId);
            }

            $branch = $this->branchRepository->find($branchId, $companyId);
            return $this->formatBranch($branch);
        });
    }

    public function getDetails(int $branchId, int $ownerId): array
    {
        $companyId = $this->branchRepository->getCompanyIdForOwner($ownerId);
        $branch = $this->branchRepository->find($branchId, $companyId);

        if (!$branch) {
            throw new Exception('الفرع غير موجود.', 404);
        }

        return $this->formatBranch($branch, withDetails: true);
    }

    public function update(int $branchId, int $ownerId, array $data): array
    {
        $companyId = $this->branchRepository->getCompanyIdForOwner($ownerId);
        $branch = $this->branchRepository->find($branchId, $companyId);

        if (!$branch) {
            throw new Exception('الفرع غير موجود.', 404);
        }

        if (isset($data['name']) && $data['name'] !== $branch->name) {
            if ($this->branchRepository->nameExists($companyId, $data['name'], $branchId)) {
                throw new Exception('يوجد فرع بنفس هذا الاسم بالفعل.', 422);
            }
        }

        return DB::transaction(function () use ($branchId, $companyId, $data) {
            $updateData = array_filter($data, fn($v) => $v !== null && $v !== '');
            unset($updateData['manager_user_id']);

            if (!empty($updateData)) {
                $this->branchRepository->update($branchId, $updateData);
            }

            if (array_key_exists('manager_user_id', $data)) {
                if (!empty($data['manager_user_id'])) {
                    $this->validateManager($data['manager_user_id']);
                    $this->branchRepository->unassignManagerFromBranch($branchId);
                    $this->branchRepository->assignManagerToBranch($data['manager_user_id'], $branchId);
                } else {
                    $this->branchRepository->unassignManagerFromBranch($branchId);
                }
            }

            $branch = $this->branchRepository->find($branchId, $companyId);
            return $this->formatBranch($branch);
        });
    }

    public function delete(int $branchId, int $ownerId): array
    {
        $companyId = $this->branchRepository->getCompanyIdForOwner($ownerId);
        $branch = $this->branchRepository->find($branchId, $companyId);

        if (!$branch) {
            throw new Exception('الفرع غير موجود.', 404);
        }

        $this->branchRepository->softDelete($branchId);

        return ['success' => true, 'message' => 'تم حذف الفرع بنجاح.'];
    }

    private function validateManager(int $managerId): void
    {
        $manager = $this->branchRepository->managerExistsInCompany($managerId);

        if (!$manager) {
            throw new Exception('المدير غير صالح — يجب أن يكون المستخدم مديرًا فعليًا.', 422);
        }
    }

    private function formatBranch(object $branch, bool $withDetails = false): array
    {
        $manager = $this->branchRepository->getManagerForBranch($branch->id);

        $result = [
            'id' => $branch->id,
            'name' => $branch->name,
            'location' => $branch->location,
            'address' => $branch->address,
            'phone' => $branch->phone,
            'email' => $branch->email,
            'status' => $branch->status,
            'manager' => $manager ? [
                'id' => $manager->id,
                'name' => $this->branchRepository->getUserFullName($manager->id),
            ] : null,
            'employees_count' => $this->branchRepository->countEmployees($branch->id),
            'departments_count' => $this->branchRepository->countDepartments($branch->id),
            'created_at' => \Carbon\Carbon::parse($branch->created_at)->toIso8601String(),
        ];

        if ($withDetails) {
            $result['updated_at'] = \Carbon\Carbon::parse($branch->updated_at)->toIso8601String();

            $departments = $this->branchRepository->getDepartmentsForBranch($branch->id);

            $result['departments'] = $departments->map(function ($dept) {
                $supervisor = null;
                if ($dept->supervisor_user_id) {
                    $supervisor = [
                        'id' => $dept->supervisor_user_id,
                        'name' => $this->branchRepository->getUserFullName($dept->supervisor_user_id),
                    ];
                }

                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'supervisor' => $supervisor,
                    'employees_count' => $this->branchRepository->countEmployeesInDepartment($dept->id),
                ];
            })->values()->all();

            // مؤقت (بنية ثابتة) لحد ما نأكد أعمدة جدول attendance_logs بالضبط
            $result['today_attendance'] = [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
            ];
        }

        return $result;
    }
}