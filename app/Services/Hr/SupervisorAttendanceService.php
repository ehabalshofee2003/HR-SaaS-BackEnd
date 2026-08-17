<?php

namespace App\Services\Hr;

use App\Models\Identity\User;
use App\Repositories\Hr\SupervisorAttendanceRepository;
use Carbon\Carbon;
use Exception;

class SupervisorAttendanceService
{
    public function __construct(private SupervisorAttendanceRepository $repo) {
 
    }

    public function recordManualAttendance(User $supervisor, array $data)
    {
        // 1. التأكد أن الموظف تابع لهذا المشرف
        if (!$this->repo->isEmployeeInTeam($supervisor->id, $data['employee_user_id'])) {
            abort(403, 'This employee is not in your team.');
        }

        // 2. التحقق من قاعدة الأعمال (لا دخول مكرر، ولا خروج بدون دخول)
        $lastLog = $this->repo->getLastLogForToday($data['employee_user_id']);

        if ($data['type'] === 'check_in') {
            if ($lastLog && !$lastLog->check_out) {
                abort(422, 'Cannot record check-in. Employee already has an open check-in session.');
            }
        } else { // check_out
            if (!$lastLog || $lastLog->check_out) {
                abort(422, 'Cannot record check-out. Employee does not have an open check-in session.');
            }
        }

        // 3. جلب بيانات الشركة والفرع من المشرف
        $context = $this->repo->getSupervisorContext($supervisor->id);

        // 4. التنفيذ
        if ($data['type'] === 'check_in') {
            $this->repo->createCheckIn($data['employee_user_id'], $data['time'], $data['notes'], $context, $supervisor->id);
        } else {
            $this->repo->updateCheckOut($lastLog->id, $data['time'], $data['notes'], $supervisor->id);
        }
    }

    public function getTeamAttendance(User $supervisor, array $filters)
    {
        return $this->repo->getAttendanceLogs($supervisor->id, $filters);
    }

    public function updateAttendanceLog(User $supervisor, int $logId, array $data)
    {
        $log = $this->repo->getLogByIdForSupervisor($supervisor->id, $logId);
        if (!$log) abort(404, 'Log not found.');

        if (isset($data['check_in'])) {
            $this->repo->updateLogTime($logId, 'check_in', $data['check_in'], $data['notes'] ?? null, $supervisor->id);
        }
        if (isset($data['check_out'])) {
            $this->repo->updateLogTime($logId, 'check_out', $data['check_out'], $data['notes'] ?? null, $supervisor->id);
        }
    }
    public function list(User $supervisor, array $filters): array
    {
        $logs = $this->repo->listForSupervisor($supervisor->id, $filters);

        $formattedLogs = array_map(fn($log) => $this->formatLog($log), $logs);
        $chart = $this->repo->monthlySummaryBySupervisor($supervisor->id);

        return [
            'logs' => $formattedLogs,
            'monthly_summary_chart' => $chart,
        ];
    }

    public function update(int $id, array $data, User $supervisor): object
    {
        $record = $this->repo->findForSupervisor($id, $supervisor->id);

        if (!$record) {
            throw new Exception('Attendance record not found.', 404);
        }

        $date = Carbon::parse($record->check_in)->toDateString();
        $newTime = Carbon::parse($date . ' ' . $data['time']);

        // يحدد أي حقل يتعدّل بناءً على نوع الحركة الأصلي للسجل نفسه
        $isCheckOutEdit = $record->check_out !== null;

        $updateData = [
            'notes' => $data['reason'],
            'reviewed_by_supervisor' => $supervisor->id,
            'reviewed_at_supervisor' => Carbon::now(),
        ];

        if ($isCheckOutEdit) {
            $updateData['check_out'] = $newTime;
            $updateData['work_hours'] = round(
                Carbon::parse($record->check_in)->diffInMinutes($newTime) / 60,
                2
            );
        } else {
            $officialStart = $newTime->copy()->setTime(8, 0);
            $updateData['check_in'] = $newTime;
            $updateData['status'] = $newTime->gt($officialStart) ? 'late' : 'present';
        }

        $this->repo->update($id, $updateData);

        return $this->repo->findForSupervisor($id, $supervisor->id);
    }

    public function createManual(array $data, User $supervisor): object
    {
        if (!$this->repo->employeeBelongsToSupervisor($data['employee_id'], $supervisor->id)) {
            throw new Exception('This employee is not assigned to you.');
        }

        $today = Carbon::today()->toDateString();
        $time = Carbon::parse($today . ' ' . $data['time']);

        if ($data['type'] === 'check_in') {
            $existing = $this->repo->findTodayLog($data['employee_id'], $today);
            if ($existing) {
                throw new Exception('This employee already has a check-in record for today.');
            }

            $officialStart = $time->copy()->setTime(8, 0);
            $status = $time->gt($officialStart) ? 'late' : 'present';

            $companyId = $supervisor->getCurrentCompanyId();
            $branchId = $supervisor->getCurrentBranchId();

            $recordId = $this->repo->createManual([
                'company_id' => $companyId,
                'employee_user_id' => $data['employee_id'],
                'branch_id' => $branchId,
                'check_in' => $time,
                'check_out' => null,
                'work_hours' => 0,
                'status' => $status,
                'notes' => $data['reason'],
                'reviewed_by_supervisor' => $supervisor->id,
                'reviewed_at_supervisor' => Carbon::now(),
            ]);

            return $this->repo->findForSupervisor($recordId, $supervisor->id);
        }

        // type === 'check_out'
        $existing = $this->repo->findTodayLog($data['employee_id'], $today);

        if (!$existing) {
            throw new Exception('No check-in record found for this employee today.');
        }

        if ($existing->check_out) {
            throw new Exception('This employee has already checked out today.');
        }

        $workHours = round(Carbon::parse($existing->check_in)->diffInMinutes($time) / 60, 2);

        $this->repo->update($existing->id, [
            'check_out' => $time,
            'work_hours' => $workHours,
            'notes' => $data['reason'],
            'reviewed_by_supervisor' => $supervisor->id,
            'reviewed_at_supervisor' => Carbon::now(),
        ]);

        return $this->repo->findForSupervisor($existing->id, $supervisor->id);
    }

    private function formatLog(object $log): array
    {
        $lateMinutes = 0;
        if ($log->status === 'late') {
            $officialStart = Carbon::parse($log->check_in)->copy()->setTime(8, 0);
            $checkIn = Carbon::parse($log->check_in);
            $lateMinutes = $checkIn->gt($officialStart) ? $officialStart->diffInMinutes($checkIn) : 0;
        }

        return [
            'id' => $log->id,
            'employee_name' => $log->employee_name,
            'method' => $log->type === 'qr' ? 'QR' : 'manual',
            'status' => $log->status,
            'check_in' => $log->check_in ? Carbon::parse($log->check_in)->format('H:i') : null,
            'check_out' => $log->check_out ? Carbon::parse($log->check_out)->format('H:i') : null,
            'working_hours' => (float) $log->work_hours,
            'lateness_minutes' => $lateMinutes,
        ];
    }
}