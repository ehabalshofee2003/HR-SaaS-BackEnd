<?php

namespace App\Services\Organization;

use App\Repositories\Organization\DepartmentRepository;
use App\Models\Identity\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class DepartmentService
{
    public function __construct(
        protected DepartmentRepository $departmentRepository
    ) {}

    public function list(User $manager, array $filters)
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->departmentRepository->paginateForBranch($branchId, $filters);
    }

    public function create(User $manager, array $data): object
    {
        $branchId = $manager->getCurrentBranchId();

        if ($this->departmentRepository->nameExistsInBranch($branchId, $data['name'])) {
            throw new Exception('اسم القسم مستخدم مسبقاً في هذا الفرع.');
        }

        $supervisorUserId = $data['supervisor_user_id'] ?? null;

        return DB::transaction(function () use ($data, $branchId, $supervisorUserId) {
            if (empty($supervisorUserId) && !empty($data['new_supervisor'])) {
                $tempPassword = Str::random(10);

                $supervisorUserId = DB::table('users')->insertGetId([
                    'phone' => $data['new_supervisor']['phone'],
                    'email' => $data['new_supervisor']['email'] ?? null,
                    'password_hash' => Hash::make($tempPassword),
                    'user_type' => 'supervisor',
                    'status' => 'active',
                    'branch_id' => $branchId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('user_profiles')->insert([
                    'user_id' => $supervisorUserId,
                    'full_name' => $data['new_supervisor']['full_name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // TODO: إرسال كلمة المرور المؤقتة عبر SMS/إشعار
            }

            $departmentId = $this->departmentRepository->create([
                'branch_id' => $branchId,
                'supervisor_user_id' => $supervisorUserId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            return $this->departmentRepository->findForBranch($departmentId, $branchId);
        });
    }

    public function getDetails(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $department = $this->departmentRepository->findForBranch($id, $branchId);

        if (!$department) {
            throw new Exception('القسم غير موجود.', 404);
        }

        $employeeCount = $this->departmentRepository->countEmployees($id);
        $presentToday = $this->departmentRepository->todayAttendanceCount($id);

        return (object) [
            'department' => $department,
            'stats' => [
                'total_employees' => $employeeCount,
                'attendance_rate_today' => $employeeCount > 0
                    ? round(($presentToday / $employeeCount) * 100, 2)
                    : 0,
            ],
        ];
    }

    public function update(int $id, array $data, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $department = $this->departmentRepository->findForBranch($id, $branchId);

        if (!$department) {
            throw new Exception('القسم غير موجود.', 404);
        }

        if (!empty($data['name']) && $data['name'] !== $department->name) {
            if ($this->departmentRepository->nameExistsInBranch($branchId, $data['name'], $id)) {
                throw new Exception('اسم القسم مستخدم مسبقاً في هذا الفرع.');
            }
        }

        $this->departmentRepository->update($id, $data);

        return $this->departmentRepository->findForBranch($id, $branchId);
    }

    public function delete(int $id, User $manager): void
    {
        $branchId = $manager->getCurrentBranchId();
        $department = $this->departmentRepository->findForBranch($id, $branchId);

        if (!$department) {
            throw new Exception('القسم غير موجود.', 404);
        }

        if ($this->departmentRepository->countActiveEmployees($id) > 0) {
            throw new Exception('لا يمكن حذف قسم يحتوي على موظفين نشطين.');
        }

        $this->departmentRepository->softDelete($id);
    }

    public function toggleStatus(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $department = $this->departmentRepository->findForBranch($id, $branchId);

        if (!$department) {
            throw new Exception('القسم غير موجود.', 404);
        }

        $newStatus = $department->status === 'active' ? 'inactive' : 'active';
        $this->departmentRepository->update($id, ['status' => $newStatus]);

        return $this->departmentRepository->findForBranch($id, $branchId);
    }

    public function assignSupervisor(int $id, int $supervisorUserId, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $department = $this->departmentRepository->findForBranch($id, $branchId);

        if (!$department) {
            throw new Exception('القسم غير موجود.', 404);
        }

        if (!$this->departmentRepository->supervisorBelongsToBranch($supervisorUserId, $branchId)) {
            throw new Exception('المشرف المحدد لا ينتمي لهذا الفرع.');
        }

        // TODO: إشعار المشرف القديم والجديد (FR-BM-05)
        $this->departmentRepository->update($id, ['supervisor_user_id' => $supervisorUserId]);

        return $this->departmentRepository->findForBranch($id, $branchId);
    }
}