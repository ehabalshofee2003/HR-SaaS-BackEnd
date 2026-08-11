<?php

namespace App\Services\Support;

use App\Repositories\Support\AnnouncementRepository;
use App\Models\Identity\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Exception;

class AnnouncementService
{
    public function __construct(private AnnouncementRepository $announcementRepository) {}

    // ================= دوال Branch Manager (جديدة) =================

    public function list(User $manager, array $filters)
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->announcementRepository->paginateForBranch($branchId, $filters);
    }

    public function create(User $manager, array $data, array $attachments = []): object
    {
        $branchId = $manager->getCurrentBranchId();
        $companyId = $manager->getCurrentCompanyId();

        // تحويل اختيار "الكل" ليصبح "الفرع" فعلياً — يمنع تسرب الإعلان لفروع أخرى
        if ($data['target'] === 'all') {
            $targetType = 'branch';
            $targetId = $branchId;
        } elseif ($data['target'] === 'department') {
            if (!$this->announcementRepository->departmentBelongsToBranch($data['target_department_id'], $branchId)) {
                throw new Exception('القسم المحدد لا ينتمي لهذا الفرع.');
            }
            $targetType = 'department';
            $targetId = $data['target_department_id'];
        } else {
            if (!$this->announcementRepository->employeeBelongsToBranch($data['target_employee_id'], $branchId)) {
                throw new Exception('الموظف المحدد لا ينتمي لهذا الفرع.');
            }
            $targetType = 'employee';
            $targetId = $data['target_employee_id'];
        }

        $uploadedPaths = [];

        try {
            $storedFiles = [];
            foreach ($attachments as $file) {
                $path = $file->store('announcements', 'public');
                $uploadedPaths[] = $path;
                $storedFiles[] = [
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                ];
            }

            $announcement = DB::transaction(function () use ($data, $companyId, $manager, $targetType, $targetId, $storedFiles) {
                $id = $this->announcementRepository->create([
                    'company_id' => $companyId,
                    'created_by' => $manager->id,
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                ]);

                foreach ($storedFiles as $file) {
                    $this->announcementRepository->attachDocument([
                        'company_id' => $companyId,
                        'announcement_id' => $id,
                        'file_name' => $file['file_name'],
                        'file_path' => $file['file_path'],
                        'mime_type' => $file['mime_type'],
                        'uploaded_by' => $manager->id,
                    ]);
                }

                // TODO: إرسال Push Notification فوري للجمهور المستهدف

                return $this->announcementRepository->findForBranch($id, $manager->getCurrentBranchId());
            });

            return $announcement;

        } catch (Exception $e) {
            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }
    }

    public function getReaders(int $id, User $manager): array
    {
        $branchId = $manager->getCurrentBranchId();
        $announcement = $this->announcementRepository->findForBranch($id, $branchId);

        if (!$announcement) {
            throw new Exception('الإعلان غير موجود.', 404);
        }

        return $this->announcementRepository->getReaders($id);
    }

    public function archive(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $announcement = $this->announcementRepository->findForBranch($id, $branchId);

        if (!$announcement) {
            throw new Exception('الإعلان غير موجود.', 404);
        }

        $this->announcementRepository->updateStatus($id, ['is_active' => false]);

        return $this->announcementRepository->findForBranch($id, $branchId);
    }

    public function delete(int $id, User $manager): void
    {
        $branchId = $manager->getCurrentBranchId();
        $announcement = $this->announcementRepository->findForBranch($id, $branchId);

        if (!$announcement) {
            throw new Exception('الإعلان غير موجود.', 404);
        }

        $this->announcementRepository->softDelete($id);
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

    public function list_Employee()
    {
        $user = $this->getAuthenticatedUser();
        $companyId = $user->getCurrentCompanyId();
        $branchId = $user->employeeDetail?->department?->branch_id;
        $departmentId = $user->employeeDetail?->department_id;

        return $this->announcementRepository->getEmployeeAnnouncements(
            $companyId, $user->id, $branchId, $departmentId
        );
    }

    public function details($id)
    {
        $user = $this->getAuthenticatedUser();
        $companyId = $user->getCurrentCompanyId();
        $branchId = $user->employeeDetail?->department?->branch_id;
        $departmentId = $user->employeeDetail?->department_id;

        $announcement = $this->announcementRepository->findAnnouncementForEmployee(
            (int) $id, $companyId, $user->id, $branchId, $departmentId
        );

        if (!$announcement) {
            return [
                'success' => false,
                'message' => 'Announcement not found.',
                'code' => 404,
                'data' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Announcement details retrieved successfully.',
            'code' => 200,
            'data' => $announcement
        ];
    }

    public function markAsRead($id)
    {
        $user = $this->getAuthenticatedUser();

        $result = $this->details($id);
        if (!$result['success']) {
            return $result;
        }

        $this->announcementRepository->markAsRead((int) $id, $user->id);
        $result['data']->load(['reads' => fn($q) => $q->where('user_id', $user->id)]);
        $result['data']->refresh();

        return [
            'success' => true,
            'message' => 'Announcement marked as read.',
            'code' => 200,
            'data' => $result['data']
        ];
    }

    private function getAuthenticatedUser(): User
    {
        $user = \App\Models\Identity\User::find(Auth::id());
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        return $user;
    }
}