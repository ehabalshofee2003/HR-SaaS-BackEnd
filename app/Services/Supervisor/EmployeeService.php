<?php

namespace App\Services\Supervisor;

use App\Repositories\Interfaces\Supervisor\EmployeeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Identity\User;

class EmployeeService
{
    public function __construct(
        private EmployeeRepositoryInterface $repository,
    ) {}

    public function list(int $supervisorId, ?string $statusFilter): array
    {
        $employees = $this->repository->list($supervisorId);

        $formatted = array_map(function ($e) {
            $status = $this->currentStatus($e->id);

            return [
                'id' => $e->id,
                'name' => $e->full_name,
                'basic_salary' => (float) $e->basic_salary,
                'attendance_status' => $status,
            ];
        }, $employees);

        if ($statusFilter) {
            $formatted = array_values(array_filter($formatted, fn($e) => $e['attendance_status'] === $statusFilter));
        }

        return $formatted;
    }

    public function get(int $id, int $supervisorId): array
    {
        $employee = $this->repository->find($id, $supervisorId);

        if (!$employee) {
            throw ValidationException::withMessages(['employee' => ['Employee not found.']]);
        }

        return [
            'id' => $employee->id,
            'name' => $employee->full_name,
            'department_name' => $employee->department_name,
            'attendance_status' => $this->currentStatus($id),
            'phone' => $employee->phone,
            'basic_salary' => (float) $employee->basic_salary,
            'hire_date' => $employee->hire_date,
            'contract_type' => $employee->contract_type,
            'national_id' => $employee->national_id,
            'job_title' => $employee->job_title,
            'employment_status' => $employee->employment_status,
        ];
    }
public function create(User $supervisor, array $data): array
{
    $branchId = $supervisor->getCurrentBranchId();

    $userId = DB::table('users')->insertGetId([
        'phone' => $data['phone'],
        'email' => $data['email'] ?? null,
        'password_hash' => Hash::make(Str::random(32)),
        'user_type' => 'employee',
        'status' => 'active',
        'branch_id' => $branchId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('user_profiles')->insert([
        'user_id' => $userId,
        'full_name' => $data['full_name'],
        'national_id' => $data['national_id'] ?? null,
        'date_of_birth' => $data['date_of_birth'] ?? null,
        'address' => $data['address'] ?? null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('employee_details')->insert([
        'user_id' => $userId,
        'department_id' => $data['department_id'],
        'supervisor_id' => $supervisor->id,
        'job_title' => $data['job_title'],
        'contract_type' => $data['contract_type'],
        'basic_salary' => $data['basic_salary'],
        'employment_status' => 'active',
        'hire_date' => $data['hire_date'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $this->get($userId, $supervisor->id);
}
    public function update(int $id, int $supervisorId, array $data): array
    {
        $employee = $this->repository->find($id, $supervisorId);

        if (!$employee) {
            throw ValidationException::withMessages(['employee' => ['Employee not found.']]);
        }

        // مسموح للمشرف يعدّل بس: job_title, employment_status
        // ممنوع: name, phone, national_id, basic_salary, contract_type, hire_date, department_id
        $employeeData = array_filter([
            'job_title' => $data['job_title'] ?? null,
            'employment_status' => $data['employment_status'] ?? null,
        ], fn($v) => $v !== null);

        $this->repository->updateProfile($id, $employeeData);

        return $this->get($id, $supervisorId);
    }

    public function attendanceToday(int $id, int $supervisorId): array
    {
        $employee = $this->repository->find($id, $supervisorId);

        if (!$employee) {
            throw ValidationException::withMessages(['employee' => ['Employee not found.']]);
        }

        $attendance = $this->repository->todayAttendance($id);

        if (!$attendance) {
            return [
                'date' => Carbon::today()->toDateString(),
                'check_in' => null,
                'check_out' => null,
                'work_hours' => 0,
                'late_minutes' => 0,
                'status' => 'absent',
            ];
        }

        $lateMinutes = 0;
        if ($attendance->status === 'late') {
            $officialStart = Carbon::parse($attendance->check_in)->copy()->setTime(8, 0);
            $checkIn = Carbon::parse($attendance->check_in);
            $lateMinutes = $checkIn->gt($officialStart) ? $officialStart->diffInMinutes($checkIn) : 0;
        }

        return [
            'date' => Carbon::parse($attendance->check_in)->toDateString(),
            'check_in' => Carbon::parse($attendance->check_in)->format('H:i:s'),
            'check_out' => $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i:s') : null,
            'work_hours' => (float) $attendance->work_hours,
            'late_minutes' => $lateMinutes,
            'status' => $attendance->status,
        ];
    }

    private function currentStatus(int $employeeUserId): string
    {
        $attendance = $this->repository->todayAttendance($employeeUserId);

        if ($attendance) {
            return $attendance->status;
        }

        if ($this->repository->hasApprovedLeaveToday($employeeUserId)) {
            return 'leave';
        }

        return 'absent';
    }
 
}