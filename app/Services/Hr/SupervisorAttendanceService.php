<?php

namespace App\Services\Hr;

use App\Models\Identity\User;
use App\Repositories\Hr\SupervisorAttendanceRepository;
use Carbon\Carbon;

class SupervisorAttendanceService
{
    public function __construct(private SupervisorAttendanceRepository $repo) {}

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
}