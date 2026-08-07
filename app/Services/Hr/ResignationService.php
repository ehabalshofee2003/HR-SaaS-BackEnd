<?php

namespace App\Services\Hr;

use App\Models\Identity\User;
use App\Repositories\Hr\ResignationRepository;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class ResignationService
{
    public function __construct(private ResignationRepository $resignationRepository) {}

    // ================= دوال Branch Manager (جديدة) =================

    public function list(User $manager, array $filters)
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->resignationRepository->paginateForBranch($branchId, $filters);
    }

    public function getDetails(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $resignation = $this->resignationRepository->findForBranch($id, $branchId);

        if (!$resignation) {
            throw new Exception('طلب الاستقالة غير موجود.', 404);
        }

        return $resignation;
    }

   public function accept(int $id, User $manager): object
{
    $branchId = $manager->getCurrentBranchId();
    $resignation = $this->resignationRepository->findForBranch($id, $branchId);

    if (!$resignation) {
        throw new Exception('طلب الاستقالة غير موجود.', 404);
    }

    if ($resignation->status !== 'pending') {
        throw new Exception('لا يمكن قبول استقالة تمت معالجتها بالفعل.');
    }

    $this->resignationRepository->updateStatusForBranch($id, [
        'status' => 'approved',
        'approved_by' => $manager->id,
        'approved_at' => Carbon::now(),
    ]);   // خلاف ذلك، التعليق يجب أن يحدث فعلياً بتاريخ last_working_date عبر Scheduled Task (لم يُبنَ بعد).
        if (Carbon::parse($resignation->last_working_date)->lte(Carbon::today())) {
            $this->resignationRepository->suspendUser($resignation->employee_user_id);
        }

        // TODO: جدولة تعليق الحساب تلقائياً بتاريخ last_working_date عبر Laravel Scheduler إذا كان بالمستقبل
        // TODO: إشعار الموظف بقبول الاستقالة

        return $this->resignationRepository->findForBranch($id, $branchId);
    }

   public function reject(int $id, User $manager, string $reason): object
{
    $branchId = $manager->getCurrentBranchId();
    $resignation = $this->resignationRepository->findForBranch($id, $branchId);

    if (!$resignation) {
        throw new Exception('طلب الاستقالة غير موجود.', 404);
    }

    if ($resignation->status !== 'pending') {
        throw new Exception('لا يمكن رفض استقالة تمت معالجتها بالفعل.');
    }

    $this->resignationRepository->updateStatusForBranch($id, [
        'status' => 'rejected',
        'rejected_by' => $manager->id,
        'rejection_reason' => $reason,
    ]);
        // TODO: إشعار الموظف بالرفض

        return $this->resignationRepository->findForBranch($id, $branchId);
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

    public function submit(array $data)
    {
        $user = $this->getAuthenticatedUser();

        $resignationData = [
            'employee_user_id' => $user->id,
            'supervisor_user_id' => $data['supervisor_user_id'],
            'reason' => $data['reason'],
            'notice_date' => $data['notice_date'],
            'last_working_date' => $data['last_working_date'],
            'status' => 'pending',
        ];

        $resignation = $this->resignationRepository->create($resignationData);
        $resignation->load('supervisor.profile');

        return [
            'success' => true,
            'message' => 'Resignation submitted successfully.',
            'code' => 201,
            'data' => $resignation
        ];
    }

    public function list_Employee()
    {
        $user = $this->getAuthenticatedUser();
        return $this->resignationRepository->getEmployeeResignations($user->id);
    }

    public function details($id)
    {
        $user = $this->getAuthenticatedUser();
        $resignation = $this->resignationRepository->findEmployeeResignationById((int) $id, $user->id);

        if (!$resignation) {
            return [
                'success' => false,
                'message' => 'Resignation not found.',
                'code' => 404,
                'data' => null
            ];
        }

        return [
            'success' => true,
            'message' => 'Resignation details retrieved successfully.',
            'code' => 200,
            'data' => $resignation
        ];
    }

    public function withdraw($id)
    {
        $user = $this->getAuthenticatedUser();
        $resignation = $this->resignationRepository->findEmployeeResignationById((int) $id, $user->id);

        if (!$resignation) {
            return [
                'success' => false,
                'message' => 'Resignation not found.',
                'code' => 404,
                'data' => null
            ];
        }

        if ($resignation->status !== 'pending') {
            return [
                'success' => false,
                'message' => 'Cannot withdraw a resignation that is already processed.',
                'code' => 400,
                'data' => null
            ];
        }

        $this->resignationRepository->updateStatus($resignation, 'withdrawn');
        $resignation->refresh();

        return [
            'success' => true,
            'message' => 'Resignation withdrawn successfully.',
            'code' => 200,
            'data' => $resignation
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