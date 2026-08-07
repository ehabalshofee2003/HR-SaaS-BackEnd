<?php

namespace App\Services\Hr;

use App\Http\Requests\Employee\StoreComplaintRequest;
use App\Repositories\Hr\ComplaintRepository;
use App\Models\Identity\User;
use Illuminate\Support\Facades\DB;
use Exception;

class ComplaintService
{
    protected $repository;

    public function __construct(ComplaintRepository $repository)
    {
        $this->repository = $repository;
    }

    // ================= دوال Branch Manager (جديدة) =================

    public function list(User $manager, array $filters)
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->repository->paginateForBranch($branchId, $filters);
    }

    public function getDetails(int $id, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $complaint = $this->repository->findForBranch($id, $branchId);

        if (!$complaint) {
            throw new Exception('الشكوى غير موجودة.', 404);
        }

        $complaint->thread = $this->repository->getThread($id);

        return $complaint;
    }

    public function reply(int $id, User $manager, string $message): object
    {
        $branchId = $manager->getCurrentBranchId();
        $complaint = $this->repository->findForBranch($id, $branchId);

        if (!$complaint) {
            throw new Exception('الشكوى غير موجودة.', 404);
        }

        if (in_array($complaint->status, ['resolved', 'closed'])) {
            throw new Exception('لا يمكن الرد على شكوى مُغلقة أو محلولة.');
        }

        DB::transaction(function () use ($id, $manager, $message, $complaint) {
            $this->repository->addMessage($id, $manager->id, $message);

            if ($complaint->status === 'open') {
                $this->repository->updateStatus($id, ['status' => 'in_review']);
            }
        });

        // TODO: إشعار الموظف صاحب الشكوى (إن لم تكن مجهولة)

        return $this->getDetails($id, $manager);
    }

    public function escalate(int $id, User $manager, string $note): object
    {
        $branchId = $manager->getCurrentBranchId();
        $complaint = $this->repository->findForBranch($id, $branchId);

        if (!$complaint) {
            throw new Exception('الشكوى غير موجودة.', 404);
        }

        if (in_array($complaint->status, ['resolved', 'closed'])) {
            throw new Exception('لا يمكن إحالة شكوى مُغلقة أو محلولة.');
        }

        DB::transaction(function () use ($id, $manager, $note) {
            $this->repository->addMessage($id, $manager->id, "[إحالة للمالك] {$note}");
        });

        // TODO: إشعار المالك بالشكوى المُحالة (لا يوجد حقل حالة "escalated" بالـ Schema حالياً؛ الحالة تبقى in_review)

        return $this->getDetails($id, $manager);
    }

    public function resolve(int $id, User $manager, ?string $response): object
    {
        $branchId = $manager->getCurrentBranchId();
        $complaint = $this->repository->findForBranch($id, $branchId);

        if (!$complaint) {
            throw new Exception('الشكوى غير موجودة.', 404);
        }

        if (in_array($complaint->status, ['resolved', 'closed'])) {
            throw new Exception('الشكوى محلولة أو مُغلقة بالفعل.');
        }

        $this->repository->updateStatus($id, [
            'status' => 'resolved',
            'response' => $response,
            'resolved_by' => $manager->id,
        ]);

        // TODO: إشعار الموظف بالحل

        return $this->getDetails($id, $manager);
    }

    public function reject(int $id, User $manager, string $reason): object
    {
        $branchId = $manager->getCurrentBranchId();
        $complaint = $this->repository->findForBranch($id, $branchId);

        if (!$complaint) {
            throw new Exception('الشكوى غير موجودة.', 404);
        }

        if (in_array($complaint->status, ['resolved', 'closed'])) {
            throw new Exception('الشكوى محلولة أو مُغلقة بالفعل.');
        }

        $this->repository->updateStatus($id, [
            'status' => 'closed',
            'response' => $reason,
            'resolved_by' => $manager->id,
        ]);

        // TODO: إشعار الموظف بالرفض

        return $this->getDetails($id, $manager);
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

    public function storeComplaint(StoreComplaintRequest $request, int $userId, int $companyId): \App\Models\Hr\Complaint
    {
        $complaintData = [
            'company_id'   => $companyId,
            'user_id'      => $userId,
            'subject'      => $request->subject,
            'description'  => $request->description,
            'status'       => 'open',
        ];

        if (isset($request->department_id)) {
            $complaintData['department_id'] = $request->department_id;
        }

        if (isset($request->is_anonymous)) {
            $complaintData['is_anonymous'] = $request->is_anonymous;
        }

        DB::beginTransaction();
        try {
            $complaint = $this->repository->create($complaintData);
            DB::commit();
            return $complaint;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getEmployeeComplaints(int $userId)
    {
        return $this->repository->getByUserId($userId);
    }
}