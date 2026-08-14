<?php

namespace App\Services\Hr;

use App\Models\Identity\User;
use App\Repositories\Hr\AttendanceRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\SaaS\CompanySetting;
use App\Repositories\Hr\QrCodeRepository;
use Exception;

class AttendanceService
{
    protected const LOCKED_STATUSES = ['approved', 'paid', 'closed'];

    public function __construct(
        private AttendanceRepository $attendanceRepository,
        private QrCodeRepository $qrCodeRepository
    ) {}

    // ================= دوال Branch Manager =================

    public function list(User $manager, array $filters)
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->attendanceRepository->paginateForBranch($branchId, $filters);
    }

    public function createManual(User $manager, array $data): object
    {
        $branchId = $manager->getCurrentBranchId();
        $companyId = $manager->getCurrentCompanyId();

        if (!$this->attendanceRepository->employeeBelongsToBranch($data['employee_user_id'], $branchId)) {
            throw new Exception('الموظف المحدد لا ينتمي لهذا الفرع.');
        }

        $this->assertPeriodNotLocked($companyId, $data['date']);

        $checkIn = Carbon::parse($data['date'] . ' ' . $data['check_in']);
        $checkOut = !empty($data['check_out'])
            ? Carbon::parse($data['date'] . ' ' . $data['check_out'])
            : null;

        $workHours = $checkOut ? round($checkOut->diffInMinutes($checkIn) / 60, 2) : 0;

        $id = $this->attendanceRepository->createManualEntry([
            'company_id' => $companyId,
            'employee_user_id' => $data['employee_user_id'],
            'branch_id' => $branchId,
            'check_in' => $checkIn->format('Y-m-d H:i:s'),
            'check_out' => $checkOut ? $checkOut->format('Y-m-d H:i:s') : null,
            'work_hours' => $workHours,
            'status' => $data['status'],
            'reason' => $data['reason'],
            'reviewed_by_manager' => $manager->id,
        ]);

        $this->attendanceRepository->logAudit([
            'user_id' => $manager->id,
            'company_id' => $companyId,
            'action' => 'create_manual',
            'entity_id' => $id,
            'new_values' => json_encode($data),
        ]);

        return $this->attendanceRepository->findForBranch($id, $branchId);
    }

    public function update(int $id, array $data, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $companyId = $manager->getCurrentCompanyId();
        $record = $this->attendanceRepository->findForBranch($id, $branchId);

        if (!$record) {
            throw new Exception('سجل الحضور غير موجود.', 404);
        }

        $recordDate = Carbon::parse($record->check_in)->format('Y-m-d');
        $this->assertPeriodNotLocked($companyId, $recordDate);

        $updateData = [];

        if (!empty($data['check_in'])) {
            $updateData['check_in'] = Carbon::parse($recordDate . ' ' . $data['check_in'])->format('Y-m-d H:i:s');
        }

        if (array_key_exists('check_out', $data) && $data['check_out']) {
            $updateData['check_out'] = Carbon::parse($recordDate . ' ' . $data['check_out'])->format('Y-m-d H:i:s');
        }

        if (!empty($updateData['check_in']) && !empty($updateData['check_out'])) {
            $updateData['work_hours'] = round(
                Carbon::parse($updateData['check_out'])->diffInMinutes(Carbon::parse($updateData['check_in'])) / 60,
                2
            );
        }

        if (!empty($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        $updateData['notes'] = $data['reason'];
        $updateData['reviewed_by_manager'] = $manager->id;
        $updateData['reviewed_at_manager'] = Carbon::now()->format('Y-m-d H:i:s');

        $this->attendanceRepository->update($id, $updateData);

        $this->attendanceRepository->logAudit([
            'user_id' => $manager->id,
            'company_id' => $companyId,
            'action' => 'update',
            'entity_id' => $id,
            'old_values' => json_encode($record),
            'new_values' => json_encode($updateData),
        ]);

        return $this->attendanceRepository->findForBranch($id, $branchId);
    }

    public function getExportData(User $manager, array $filters): array
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->attendanceRepository->getForExport($branchId, $filters);
    }

    private function assertPeriodNotLocked(int $companyId, string $date): void
    {
        $status = $this->attendanceRepository->payrollPeriodStatusForDate($companyId, $date);

        if ($status && in_array($status, self::LOCKED_STATUSES)) {
            throw new Exception('لا يمكن تعديل حضور تم اعتماده ضمن كشف راتب مقفل.');
        }
    }

    // ================= دوال Employee/Supervisor Mobile (الأصلية — لم تُمس) =================

    public function getTodayStatus()
    {
        $user = $this->getAuthenticatedUser();
        $log = $this->attendanceRepository->getTodayLog($user->id);

        return [
            'success' => true,
            'code' => 200,
            'data' => $log
        ];
    }

    public function checkOut(array $notes = null)
    {
        $user = $this->getAuthenticatedUser();
        $activeLog = $this->attendanceRepository->findActiveCheckInToday($user->id);

        if (!$activeLog) {
            return [
                'success' => false,
                'message' => 'No active check-in found for today.',
                'code' => 400,
                'data' => null
            ];
        }

        $now = Carbon::now();
        $checkInTime = Carbon::parse($activeLog->check_in);

        $workHours = round($checkInTime->diffInMinutes($now) / 60, 2);

        $this->attendanceRepository->updateCheckOut($activeLog, $now->toDateTimeString(), $workHours);

        $activeLog->refresh();

        return [
            'success' => true,
            'message' => 'Checked out successfully.',
            'code' => 200,
            'data' => $activeLog
        ];
    }

    public function getHistory()
    {
        $user = $this->getAuthenticatedUser();
        return $this->attendanceRepository->getHistory($user->id);
    }

    private function getAuthenticatedUser(): User
    {
        $user = \App\Models\Identity\User::find(Auth::id());
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        return $user;
    }

    public function checkIn(int $employeeUserId, string $qrCode): array
    {
        $qr = $this->qrCodeRepository->findByCode($qrCode);

        if (!$qr) {
            return ['success' => false, 'message' => 'Invalid QR code', 'code' => 404];
        }

        if (Carbon::parse($qr->expires_at)->lt(now())) {
            return ['success' => false, 'message' => 'QR code expired', 'code' => 422];
        }

        if ($qr->type !== 'check_in') {
            return ['success' => false, 'message' => 'This QR is not for check-in', 'code' => 422];
        }
        $employeeBranchId = \App\Models\Identity\User::find($employeeUserId)?->branch_id;

        if ($employeeBranchId != $qr->branch_id) {
            return ['success' => false, 'message' => 'QR code does not belong to your branch', 'code' => 403];
        }

        if ($this->attendanceRepository->hasActiveCheckInToday($employeeUserId)) {
            return ['success' => false, 'message' => 'Already checked in today', 'code' => 422];
        }

        $companyId = \App\Models\Identity\User::find($employeeUserId)
            ->getCurrentCompanyId();

        $status = $this->determineAttendanceStatus($companyId);

        $log = $this->attendanceRepository->create([
            'company_id'       => $companyId,
            'employee_user_id' => $employeeUserId,
            'branch_id'        => $qr->branch_id,
            'qr_code_id'       => $qr->id,
            'check_in'         => now(),
            'type'             => 'qr',
            'status'           => $status,
        ]);

        return ['success' => true, 'log' => $log];
    }

    private function determineAttendanceStatus(int $companyId): string
    {
        $setting = CompanySetting::where('company_id', $companyId)
            ->where('key', 'work_start_time')
            ->first();

        if (!$setting || !$setting->value) {
            return 'present';
        }

        $workStart = Carbon::parse($setting->value);

        if (now()->gt($workStart)) {
            return 'late';
        }

        return 'present';
    }
}