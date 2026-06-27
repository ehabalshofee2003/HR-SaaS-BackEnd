<?php

namespace App\Services\Hr;

use App\Http\Requests\Employee\StoreComplaintRequest;
use App\Repositories\Hr\ComplaintRepository;
use Illuminate\Support\Facades\DB;

class ComplaintService
{
    protected $repository;

    public function __construct(ComplaintRepository $repository)
    {
        $this->repository = $repository;
    }

    public function storeComplaint(StoreComplaintRequest $request, int $userId, int $companyId): \App\Models\Hr\Complaint
    {
        $complaintData = [
            'company_id'   => $companyId,
            'user_id'      => $userId, // نحتفظ بالـ ID لتمكين الموظف من تتبع شكواه
            'subject'      => $request->subject,
            'description'  => $request->description,
            'status'       => 'open',
        ];

        // تطبيق القاعدة 8: استخدام isset للحقول الاختيارية
        if (isset($request->department_id)) {
            $complaintData['department_id'] = $request->department_id;
        }

        if (isset($request->is_anonymous)) {
            $complaintData['is_anonymous'] = $request->is_anonymous;
        }

        // لا يوجد ملفات هنا، لذا نستخدم Transaction عادية لحماية البيانات
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