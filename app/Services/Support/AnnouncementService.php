<?php

namespace App\Services\Support;

use App\Models\Identity\User;
use App\Repositories\Support\AnnouncementRepository;
use Illuminate\Support\Facades\Auth;

class AnnouncementService
{
    public function __construct(private AnnouncementRepository $announcementRepository) {}

    public function list()
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
        
        // التحقق الأمني أولاً
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