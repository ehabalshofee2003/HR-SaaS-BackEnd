<?php

namespace App\Services\Hr;

use App\Repositories\Hr\QrCodeRepository;
use App\Repositories\Hr\AttendanceRepository;
use App\Models\Identity\User;
use Carbon\Carbon;
use Exception;
class AttendanceService
{
    public function __construct(
        protected QrCodeRepository $qrCodeRepository,
        protected AttendanceRepository $attendanceRepository,
    ) {}

    public function checkIn(int $employeeUserId, string $qrCode): array
    {
        $qr = $this->qrCodeRepository->findByCode($qrCode);

        if (!$qr) {
            return ['success' => false, 'message' => 'Invalid QR code.', 'code' => 404];
        }

        if ($qr->used_at) {
            return ['success' => false, 'message' => 'This QR code has already been used.', 'code' => 422];
        }

        if (Carbon::parse($qr->expires_at)->lt(now())) {
            return ['success' => false, 'message' => 'QR code expired.', 'code' => 422];
        }

        if ($qr->type !== 'check_in') {
            return ['success' => false, 'message' => 'This QR is not for check-in.', 'code' => 422];
        }

        $employee = User::find($employeeUserId);
        $employeeBranchId = $employee?->branch_id;

        if ($employeeBranchId != $qr->branch_id) {
            return ['success' => false, 'message' => 'QR code does not belong to your branch.', 'code' => 403];
        }

        if ($this->attendanceRepository->hasCheckedInToday($employeeUserId)) {
                return ['success' => false, 'message' => 'Already checked in today.', 'code' => 422];
        }

        $companyId = $employee->getCurrentCompanyId();
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

        $this->qrCodeRepository->markUsed($qr->id);

        return [
            'success' => true,
            'code' => 200,
            'data' => [
                'check_in_time' => Carbon::parse($log->check_in)->format('H:i'),
                'date' => Carbon::parse($log->check_in)->toDateString(),
                'status' => $log->status,
            ],
        ];
    }

    public function checkOut(int $employeeUserId, string $qrCode): array
    {
        $qr = $this->qrCodeRepository->findByCode($qrCode);

        if (!$qr) {
            return ['success' => false, 'message' => 'Invalid QR code.', 'code' => 404];
        }

        if ($qr->used_at) {
            return ['success' => false, 'message' => 'This QR code has already been used.', 'code' => 422];
        }

        if (Carbon::parse($qr->expires_at)->lt(now())) {
            return ['success' => false, 'message' => 'QR code expired.', 'code' => 422];
        }

        if ($qr->type !== 'check_out') {
            return ['success' => false, 'message' => 'This QR is not for check-out.', 'code' => 422];
        }

        $employee = User::find($employeeUserId);

        if ($employee?->branch_id != $qr->branch_id) {
            return ['success' => false, 'message' => 'QR code does not belong to your branch.', 'code' => 403];
        }

        $log = $this->attendanceRepository->findActiveCheckInToday($employeeUserId);
        if (!$log) {
            return ['success' => false, 'message' => 'No active check-in found for today.', 'code' => 422];
        }

        $checkOutTime = now();
        $workHours = round(Carbon::parse($log->check_in)->diffInMinutes($checkOutTime) / 60, 2);

        $this->attendanceRepository->update($log->id, [
            'check_out' => $checkOutTime,
            'work_hours' => $workHours,
        ]);

        $this->qrCodeRepository->markUsed($qr->id);

        return [
            'success' => true,
            'code' => 200,
            'data' => [
                'check_out_time' => $checkOutTime->format('H:i'),
                'work_hours' => $workHours,
                'date' => Carbon::parse($log->check_in)->toDateString(),
            ],
        ];
    }

    private function determineAttendanceStatus(?int $companyId): string
    {
        $officialStart = now()->copy()->setTime(8, 0);

        return now()->gt($officialStart) ? 'late' : 'present';
    }
        // ================= دوال Branch Manager (جديدة) =================

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
        throw new Exception('الموظف المحدد لا ينتمي إلى هذا الفرع.');
    }

    $checkIn = Carbon::parse($data['date'] . ' ' . $data['check_in']);
    $checkOut = !empty($data['check_out'])
        ? Carbon::parse($data['date'] . ' ' . $data['check_out'])
        : null;

    $workHours = $checkOut ? round($checkIn->diffInMinutes($checkOut) / 60, 2) : 0;

    $recordId = $this->attendanceRepository->createManualEntry([
        'company_id' => $companyId,
        'employee_user_id' => $data['employee_user_id'],
        'branch_id' => $branchId,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'work_hours' => $workHours,
        'status' => $data['status'],
        'reason' => $data['reason'],
        'reviewed_by_manager' => $manager->id,
    ]);

    $this->attendanceRepository->logAudit([
        'user_id' => $manager->id,
        'company_id' => $companyId,
        'action' => 'create_manual_attendance',
        'entity_id' => $recordId,
        'new_values' => json_encode($data),
    ]);

    return $this->attendanceRepository->findForBranch($recordId, $branchId);
}

    public function update(int $id, array $data, User $manager): object
    {
        $branchId = $manager->getCurrentBranchId();
        $record = $this->attendanceRepository->findForBranch($id, $branchId);

        if (!$record) {
            throw new Exception('سجل الحضور غير موجود.', 404);
        }

        if (!empty($data['check_in']) && !empty($data['check_out'])) {
            $data['work_hours'] = round(
                Carbon::parse($data['check_in'])->diffInMinutes(Carbon::parse($data['check_out'])) / 60,
                2
            );
        }

        $this->attendanceRepository->update($id, $data);

        $this->attendanceRepository->logAudit([
            'user_id' => $manager->id,
            'company_id' => $manager->getCurrentCompanyId(),
            'action' => 'update_attendance',
            'entity_id' => $id,
            'old_values' => json_encode($record),
            'new_values' => json_encode($data),
        ]);

        return $this->attendanceRepository->findForBranch($id, $branchId);
    }

    public function getExportData(User $manager, array $filters): array
    {
        $branchId = $manager->getCurrentBranchId();
        return $this->attendanceRepository->getForExport($branchId, $filters);
    }
}