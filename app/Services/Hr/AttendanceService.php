<?php

namespace App\Services\Hr;

use App\Models\Identity\User;
use App\Repositories\Hr\AttendanceRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\SaaS\CompanySetting;
use App\Repositories\Hr\QrCodeRepository;
class AttendanceService
{
    public function __construct(private AttendanceRepository $attendanceRepository, private QrCodeRepository $qrCodeRepository) {}

    // === الدوال الجديدة ===

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
        
        // حساب ساعات العمل بالدقة العشرية (ساعات)
        $workHours = round($checkInTime->diffInMinutes($now) / 60, 2);

        $this->attendanceRepository->updateCheckOut($activeLog, $now->toDateTimeString(), $workHours);

        // إعادة تحميل اللوج ليرجع بالبيانات المحدثة للـ Resource
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

    /**
     * قاعدة Auth Type Hinting الصارمة
     */
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

        $employeeBranchId = \App\Models\Identity\User::find($employeeUserId)
            ?->employeeDetail?->department?->branch_id;

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