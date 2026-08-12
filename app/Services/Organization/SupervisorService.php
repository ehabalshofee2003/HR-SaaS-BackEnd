<?php

namespace App\Services\Organization;

use App\Repositories\Organization\SupervisorRepository;
use App\Models\Identity\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class SupervisorService
{
    public function __construct(
        protected SupervisorRepository $supervisorRepository
    ) {}

    public function list(User $manager, array $filters)
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->supervisorRepository->paginateForBranch($branchId, $filters);
    }

public function create(User $manager, array $data): object
{
    $branchId = $manager->getCurrentBranchId();

    return DB::transaction(function () use ($data, $branchId) {
        $tempPassword = Str::random(10);

        $supervisorId = $this->supervisorRepository->create([
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password_hash' => Hash::make($tempPassword),
            'branch_id' => $branchId,
        ]);

        $this->supervisorRepository->createProfile($supervisorId, $data['full_name']);

        if (!empty($data['department_id'])) {
            $this->supervisorRepository->assignToDepartment($supervisorId, $data['department_id']);
        }

        // تعيين role "supervisor" فور إنشاء الحساب (Spatie Permission)
        $newSupervisor = User::find($supervisorId);
        $newSupervisor->assignRole('supervisor');

        // TODO: إرسال إشعار بكلمة المرور المؤقتة عبر SMS

        return $this->supervisorRepository->findForBranch($supervisorId, $branchId);
    });
}

    public function getDetails(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $supervisor = $this->supervisorRepository->findForBranch($id, $branchId);

        if (!$supervisor) {
            throw new Exception('المشرف غير موجود.', 404);
        }

        $department = $this->supervisorRepository->currentDepartment($id);
        $employeeCount = $this->supervisorRepository->countSupervisedEmployees($id);

        return (object) [
            'supervisor' => $supervisor,
            'department' => $department,
            'supervised_employees_count' => $employeeCount,
        ];
    }

    public function update(int $id, array $data, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $supervisor = $this->supervisorRepository->findForBranch($id, $branchId);

        if (!$supervisor) {
            throw new Exception('المشرف غير موجود.', 404);
        }

        $userData = array_intersect_key($data, array_flip(['phone', 'email']));
        if (!empty($userData)) {
            $this->supervisorRepository->update($id, $userData);
        }

        if (!empty($data['full_name'])) {
            $this->supervisorRepository->updateProfileName($id, $data['full_name']);
        }

        if (array_key_exists('department_id', $data)) {
            $this->supervisorRepository->unassignFromDepartment($id);
            if (!empty($data['department_id'])) {
                $this->supervisorRepository->assignToDepartment($id, $data['department_id']);
            }
        }

        return $this->supervisorRepository->findForBranch($id, $branchId);
    }

    public function delete(int $id, User $manager): void
    {
        $branchId = $manager->getCurrentBranchId();
        $supervisor = $this->supervisorRepository->findForBranch($id, $branchId);

        if (!$supervisor) {
            throw new Exception('المشرف غير موجود.', 404);
        }

        $department = $this->supervisorRepository->currentDepartment($id);
        if ($department) {
            throw new Exception('لا يمكن حذف مشرف مُسند لقسم نشط. يرجى تغيير مشرف القسم أولاً.');
        }

        $this->supervisorRepository->softDelete($id);
    }

    public function toggleStatus(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $supervisor = $this->supervisorRepository->findForBranch($id, $branchId);

        if (!$supervisor) {
            throw new Exception('المشرف غير موجود.', 404);
        }

        $newStatus = $supervisor->status === 'active' ? 'suspended' : 'active';
        $this->supervisorRepository->update($id, ['status' => $newStatus]);

        return $this->supervisorRepository->findForBranch($id, $branchId);
    }

    public function resetPassword(int $id, User $manager): string
    {
        $branchId = $manager->getCurrentBranchId();
        $supervisor = $this->supervisorRepository->findForBranch($id, $branchId);

        if (!$supervisor) {
            throw new Exception('المشرف غير موجود.', 404);
        }

        $newPassword = Str::random(10);
        $this->supervisorRepository->update($id, ['password_hash' => Hash::make($newPassword)]);

        // TODO: إرسال كلمة المرور الجديدة عبر SMS

        return $newPassword;
    }
}
