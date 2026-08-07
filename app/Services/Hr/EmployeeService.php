<?php

namespace App\Services\Hr;

use App\Repositories\Hr\EmployeeRepository;
use App\Models\Identity\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Exception;

class EmployeeService
{
    public function __construct(
        protected EmployeeRepository $employeeRepository
    ) {}

    public function list(User $manager, array $filters)
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->employeeRepository->paginateForBranch($branchId, $filters);
    }

    public function create(User $manager, array $data, ?UploadedFile $avatar, array $documents = []): object
    {
        $branchId = $manager->getCurrentBranchId();

        $department = $this->employeeRepository->departmentBelongsToBranch($data['department_id'], $branchId);
        if (!$department) {
            throw new Exception('القسم المحدد غير موجود في هذا الفرع.');
        }

        // === رفع الملفات خارج الـ Transaction (القاعدة 3) ===
        $uploadedPaths = [];
        $avatarPath = null;

        try {
            if ($avatar) {
                $avatarPath = $avatar->store('employees/avatars', 'public');
                $uploadedPaths[] = $avatarPath;
            }

            $storedDocuments = [];
            foreach ($documents as $doc) {
                /** @var UploadedFile $file */
                $file = $doc['file'];
                $path = $file->store('employees/documents', 'public');
                $uploadedPaths[] = $path;
                $storedDocuments[] = [
                    'type' => $doc['type'],
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                ];
            }

            $employee = DB::transaction(function () use ($data, $branchId, $department, $avatarPath, $storedDocuments, $manager) {
                $tempPassword = Str::random(10);

                $userId = $this->employeeRepository->createUser([
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'password_hash' => Hash::make($tempPassword),
                    'branch_id' => $branchId,
                ]);

                $this->employeeRepository->createProfile($userId, [
                    'full_name' => $data['full_name'],
                    'avatar' => $avatarPath,
                    'national_id' => $data['national_id'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'address' => $data['address'] ?? null,
                ]);

                $this->employeeRepository->createEmployeeDetail($userId, [
                    'department_id' => $data['department_id'],
                    'supervisor_id' => $department->supervisor_user_id,
                    'job_title' => $data['job_title'],
                    'contract_type' => $data['contract_type'],
                    'basic_salary' => $data['basic_salary'],
                    'hire_date' => $data['hire_date'],
                ]);

                foreach ($storedDocuments as $doc) {
                    $this->employeeRepository->createDocument([
                        'company_id' => $manager->getCurrentCompanyId(),
                        'user_id' => $userId,
                        'type' => $doc['type'],
                        'file_name' => $doc['file_name'],
                        'file_path' => $doc['file_path'],
                        'mime_type' => $doc['mime_type'],
                        'uploaded_by' => $manager->id,
                    ]);
                }

                // TODO: إرسال إشعار للموظف الجديد بكلمة المرور المؤقتة

                return $this->employeeRepository->findForBranch($userId, $branchId);
            });

            return $employee;

        } catch (Exception $e) {
            // === تنظيف الملفات المرفوعة إذا فشلت الداتا بيز (القاعدة 3) ===
            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }
    }

    public function getDetails(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $employee = $this->employeeRepository->findForBranch($id, $branchId);

        if (!$employee) {
            throw new Exception('الموظف غير موجود.', 404);
        }

        return $employee;
    }

    public function update(int $id, array $data, User $manager, ?UploadedFile $avatar = null): object
    {
        $branchId = $manager->getCurrentBranchId();
        $employee = $this->employeeRepository->findForBranch($id, $branchId);

        if (!$employee) {
            throw new Exception('الموظف غير موجود.', 404);
        }

        if (!empty($data['department_id'])) {
            $department = $this->employeeRepository->departmentBelongsToBranch($data['department_id'], $branchId);
            if (!$department) {
                throw new Exception('القسم المحدد غير موجود في هذا الفرع.');
            }
        }

        $newAvatarPath = null;
        try {
            if ($avatar) {
                $newAvatarPath = $avatar->store('employees/avatars', 'public');
            }

            DB::transaction(function () use ($id, $data, $newAvatarPath, $employee) {
                $userData = array_intersect_key($data, array_flip(['phone', 'email']));
                $this->employeeRepository->updateUser($id, $userData);

                $profileData = array_intersect_key($data, array_flip(['full_name', 'national_id', 'date_of_birth', 'address']));
                if ($newAvatarPath) {
                    $profileData['avatar'] = $newAvatarPath;
                }
                $this->employeeRepository->updateProfile($id, $profileData);

                $detailData = array_intersect_key($data, array_flip(['department_id', 'supervisor_id', 'job_title', 'contract_type', 'basic_salary', 'hire_date']));
                $this->employeeRepository->updateEmployeeDetail($id, $detailData);
            });

            // حذف الصورة القديمة بعد نجاح التحديث فقط
            if ($newAvatarPath && !empty($employee->avatar)) {
                Storage::disk('public')->delete($employee->avatar);
            }

        } catch (Exception $e) {
            if ($newAvatarPath) {
                Storage::disk('public')->delete($newAvatarPath);
            }
            throw $e;
        }

        return $this->employeeRepository->findForBranch($id, $branchId);
    }

    public function delete(int $id, User $manager): void
    {
        $branchId = $manager->getCurrentBranchId();
        $employee = $this->employeeRepository->findForBranch($id, $branchId);

        if (!$employee) {
            throw new Exception('الموظف غير موجود.', 404);
        }

        if ($this->employeeRepository->hasPaidPayrollRecords($id)) {
            throw new Exception('لا يمكن حذف موظف لديه سجلات رواتب مدفوعة.');
        }

        $this->employeeRepository->softDelete($id);
    }

    public function toggleStatus(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $employee = $this->employeeRepository->findForBranch($id, $branchId);

        if (!$employee) {
            throw new Exception('الموظف غير موجود.', 404);
        }

        $newStatus = $employee->status === 'active' ? 'suspended' : 'active';
        $this->employeeRepository->updateUser($id, ['status' => $newStatus]);

        return $this->employeeRepository->findForBranch($id, $branchId);
    }

    public function resetPassword(int $id, User $manager): string
    {
        $branchId = $manager->getCurrentBranchId();
        $employee = $this->employeeRepository->findForBranch($id, $branchId);

        if (!$employee) {
            throw new Exception('الموظف غير موجود.', 404);
        }

        $newPassword = Str::random(10);
        $this->employeeRepository->updateUser($id, ['password_hash' => Hash::make($newPassword)]);

        // TODO: إرسال كلمة المرور الجديدة عبر SMS

        return $newPassword;
    }

    public function listDocuments(int $id, User $manager): array
    {
        $branchId = $manager->getCurrentBranchId();
        $employee = $this->employeeRepository->findForBranch($id, $branchId);

        if (!$employee) {
            throw new Exception('الموظف غير موجود.', 404);
        }

        return $this->employeeRepository->listDocuments($id);
    }

    public function uploadDocument(int $id, UploadedFile $file, string $type, User $manager): object
    {
        $branchId = $manager->getCurrentCompanyId() ? $manager->getCurrentBranchId() : null;
        $employee = $this->employeeRepository->findForBranch($id, $manager->getCurrentBranchId());

        if (!$employee) {
            throw new Exception('الموظف غير موجود.', 404);
        }

        $path = $file->store('employees/documents', 'public');

        try {
            $this->employeeRepository->createDocument([
                'company_id' => $manager->getCurrentCompanyId(),
                'user_id' => $id,
                'type' => $type,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'uploaded_by' => $manager->id,
            ]);
        } catch (Exception $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        return (object) ['file_path' => $path, 'type' => $type];
    }

    public function deleteDocument(int $employeeId, int $documentId, User $manager): void
    {
        $branchId = $manager->getCurrentBranchId();
        $employee = $this->employeeRepository->findForBranch($employeeId, $branchId);

        if (!$employee) {
            throw new Exception('الموظف غير موجود.', 404);
        }

        $document = $this->employeeRepository->findDocument($documentId, $employeeId);
        if (!$document) {
            throw new Exception('المستند غير موجود.', 404);
        }

        $this->employeeRepository->deleteDocument($documentId);
        Storage::disk('public')->delete($document->file_path);
    }
}