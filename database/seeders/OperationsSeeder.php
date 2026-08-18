<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAttendance();
        $this->seedLeaveBalancesAndRequests();
        $this->seedExceptionRequests();
        $this->seedTasks();
    }

    private function seedAttendance(): void
    {
        $companyId = OrganizationSeeder::$companyId;

        foreach (StaffSeeder::$employeeIds as $employeeId) {
            $branchId = StaffSeeder::$employeeBranch[$employeeId];
            $rows = [];

            for ($d = 89; $d >= 0; $d--) {
                $date = Carbon::now()->subDays($d);
                if ($date->isFriday()) continue;

                $roll = rand(1, 100);
                if ($roll <= 8) continue;

                $isLate = $roll <= 22;
                $checkIn = $date->copy()->setTime($isLate ? 9 : 8, $isLate ? rand(30, 59) : rand(0, 59));
                $checkOut = $checkIn->copy()->addHours(8)->addMinutes(rand(-10, 30));

                $rows[] = [
                    'company_id' => $companyId, 'employee_user_id' => $employeeId, 'branch_id' => $branchId,
                    'check_in' => $checkIn, 'check_out' => $checkOut,
                    'work_hours' => round($checkIn->diffInMinutes($checkOut) / 60, 2),
                    'type' => 'qr', 'status' => $isLate ? 'late' : 'present',
                    'created_at' => now(), 'updated_at' => now(),
                ];
            }

            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('attendance_logs')->insert($chunk);
            }
        }
    }

    private function seedLeaveBalancesAndRequests(): void
    {
        $year = now()->year;

        foreach (StaffSeeder::$employeeIds as $employeeId) {
            foreach (StaffSeeder::$leaveTypeIds as $code => $typeId) {
                $max = match ($code) { 'annual' => 21, 'sick' => 14, 'emergency' => 5, default => 30 };
                DB::table('leave_balances')->insert([
                    'employee_user_id' => $employeeId, 'leave_type_id' => $typeId, 'year' => $year,
                    'remaining_days' => rand(intval($max * 0.3), $max),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        $reasons = ['Family event out of town.', 'Doctor appointment and recovery.', 'Personal matters to attend to.'];
        $statuses = ['pending_manager', 'approved', 'rejected', 'cancelled'];
        $companyId = OrganizationSeeder::$companyId;

        $sampleSize = min(4, count(StaffSeeder::$employeeIds));
        $sample = collect(StaffSeeder::$employeeIds)->random($sampleSize);

        foreach ($sample as $i => $employeeId) {
            $start = Carbon::now()->addDays(rand(-60, 30));
            DB::table('leave_requests')->insert([
                'company_id' => $companyId, 'employee_id' => $employeeId,
                'leave_type_id' => array_values(StaffSeeder::$leaveTypeIds)[$i % 4],
                'start_date' => $start->toDateString(), 'end_date' => $start->copy()->addDays(rand(1, 4))->toDateString(),
                'reason' => $reasons[$i % count($reasons)], 'status' => $statuses[$i % count($statuses)],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedExceptionRequests(): void
    {
        $reasons = [
            'Stuck in traffic due to road closure.', 'Requesting review of last deduction, recorded by mistake.',
            'Worked extra hours covering a colleague shift.', 'Family emergency required leaving early.',
        ];
        $statuses = ['pending', 'pending_manager', 'approved', 'rejected'];
        $companyId = OrganizationSeeder::$companyId;

        $sampleSize = min(4, count(StaffSeeder::$employeeIds));
        $sample = collect(StaffSeeder::$employeeIds)->random($sampleSize);

        foreach ($sample as $i => $employeeId) {
            DB::table('exception_requests')->insert([
                'company_id' => $companyId,
                'employee_id' => StaffSeeder::$employeeDetailId[$employeeId],
                'exception_type_id' => StaffSeeder::$exceptionTypeIds[$i % count(StaffSeeder::$exceptionTypeIds)],
                'request_date' => Carbon::now()->subDays(rand(0, 60))->toDateString(),
                'reason' => $reasons[$i % count($reasons)], 'status' => $statuses[$i % count($statuses)],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedTasks(): void
    {
        $titles = [
            'Restock shelves in aisle 3', 'Prepare weekly inventory report', 'Clean and organize storage room',
            'Update product price tags', 'Train new team member on POS system', 'Submit monthly expense report',
        ];
        $priorities = ['low', 'medium', 'high'];
        $statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        $companyId = OrganizationSeeder::$companyId;

        foreach (StaffSeeder::$employeeIds as $i => $employeeId) {
            $status = $statuses[$i % count($statuses)];
            DB::table('tasks')->insert([
                'company_id' => $companyId, 'employee_user_id' => $employeeId,
                'supervisor_user_id' => StaffSeeder::$employeeSupervisor[$employeeId],
                'title' => $titles[$i % count($titles)],
                'description' => 'Please complete this task before the due date and report back.',
                'type' => 'ad_hoc', 'priority' => $priorities[$i % 3],
                'due_date' => Carbon::now()->addDays(rand(-10, 14)), 'status' => $status,
                'completed_at' => $status === 'completed' ? now() : null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}