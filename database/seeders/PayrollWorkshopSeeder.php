<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollWorkshopSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPayroll();
        $this->seedWorkshops();
        $this->seedAnnouncements();
        $this->seedComplaints();
        $this->seedResignations();
        $this->seedNotifications();
    }

    private function seedPayroll(): void
    {
        $companyId = OrganizationSeeder::$companyId;
        $basicSalaries = DB::table('employee_details')->pluck('basic_salary', 'user_id');

        $periods = [
            ['month' => now()->subMonth()->month, 'year' => now()->subMonth()->year, 'status' => 'paid'],
            ['month' => now()->month, 'year' => now()->year, 'status' => 'calculated'],
        ];

        foreach ($periods as $p) {
            $periodId = DB::table('payroll_periods')->insertGetId([
                'company_id' => $companyId, 'month' => $p['month'], 'year' => $p['year'],
                'start_date' => Carbon::create($p['year'], $p['month'], 1)->toDateString(),
                'end_date' => Carbon::create($p['year'], $p['month'], 1)->endOfMonth()->toDateString(),
                'status' => $p['status'], 'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach (StaffSeeder::$employeeIds as $employeeId) {
                $base = (float) ($basicSalaries[$employeeId] ?? 500);
                $bonus = rand(0, 1) ? rand(20, 80) : 0;
                $overtime = rand(0, 1) ? rand(15, 60) : 0;
                $deduction = rand(0, 1) ? rand(10, 40) : 0;
                $gross = $base + $bonus + $overtime;
                $net = $gross - $deduction;
                $recordStatus = $p['status'] === 'paid' ? 'paid' : 'draft';

                $recordId = DB::table('payroll_records')->insertGetId([
                    'employee_user_id' => $employeeId, 'period_id' => $periodId,
                    'gross_salary' => $gross, 'total_deductions' => $deduction, 'total_bonuses' => $bonus,
                    'net_salary' => $net, 'status' => $recordStatus,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                $components = [['name' => 'Base Salary', 'component_type' => 'base_salary', 'amount' => $base]];
                if ($bonus > 0) $components[] = ['name' => 'Performance Bonus', 'component_type' => 'bonus', 'amount' => $bonus];
                if ($overtime > 0) $components[] = ['name' => 'Overtime', 'component_type' => 'overtime', 'amount' => $overtime];
                if ($deduction > 0) $components[] = ['name' => 'Late/Absence Deduction', 'component_type' => 'deduction', 'amount' => $deduction];

                foreach ($components as $c) {
                    DB::table('payroll_record_details')->insert(array_merge($c, [
                        'record_id' => $recordId, 'created_at' => now(), 'updated_at' => now(),
                    ]));
                }
            }
        }
    }

    private function seedWorkshops(): void
    {
        $workshops = [
            ['title' => 'Customer Service Excellence', 'location' => 'Main Hall', 'days_from_now' => 10],
            ['title' => 'POS System Update Training', 'location' => 'Training Room A', 'days_from_now' => 18],
        ];
        $companyId = OrganizationSeeder::$companyId;
        $branchId = OrganizationSeeder::$branchIds[0]; // الفرع الحقيقي اللي فيه الموظفين

        foreach ($workshops as $w) {
            $start = Carbon::now()->addDays($w['days_from_now'])->setTime(10, 0);

            $workshopId = DB::table('workshops')->insertGetId([
                'company_id' => $companyId, 'branch_id' => $branchId,
                'created_by' => OrganizationSeeder::$managerIds[$branchId],
                'title' => $w['title'], 'description' => 'Internal staff development workshop.',
                'location' => $w['location'], 'start_date' => $start, 'end_date' => $start->copy()->addHours(2),
                'capacity' => 15, 'audience' => 'employee', 'status' => 'upcoming',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $attendeeCount = min(3, count(StaffSeeder::$employeeIds));
            $attendees = collect(StaffSeeder::$employeeIds)->random($attendeeCount);
            foreach ($attendees as $employeeId) {
                DB::table('workshop_attendees')->insert([
                    'workshop_id' => $workshopId, 'employee_user_id' => $employeeId,
                    'status' => 'registered', 'registered_at' => now(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedAnnouncements(): void
    {
        $announcements = [
            ['title' => 'Holiday Schedule Update', 'content' => 'Please note the updated holiday schedule for next month.'],
            ['title' => 'New Break Room Opening', 'content' => 'The renovated break room will open next week for all staff.'],
        ];
        $companyId = OrganizationSeeder::$companyId;
        $branchId = OrganizationSeeder::$branchIds[0];

        foreach ($announcements as $a) {
            DB::table('announcements')->insert([
                'company_id' => $companyId, 'created_by' => OrganizationSeeder::$managerIds[$branchId],
                'title' => $a['title'], 'content' => $a['content'], 'target_type' => 'branch', 'target_id' => $branchId,
                'start_date' => now()->toDateString(), 'end_date' => now()->addDays(30)->toDateString(),
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedComplaints(): void
    {
        $complaints = [
            ['subject' => 'Break room AC not working', 'description' => 'The break room air conditioning has not been working for a week.', 'status' => 'open'],
            ['subject' => 'Shift scheduling fairness', 'description' => 'Concern regarding shift scheduling fairness across the team.', 'status' => 'resolved'],
        ];
        $companyId = OrganizationSeeder::$companyId;
        $depts = DB::table('departments')->pluck('id');

        foreach ($complaints as $i => $c) {
            DB::table('complaints')->insert([
                'company_id' => $companyId, 'user_id' => StaffSeeder::$employeeIds[$i],
                'department_id' => $depts[$i % count($depts)],
                'subject' => $c['subject'], 'description' => $c['description'], 'status' => $c['status'],
                'response' => $c['status'] === 'resolved' ? 'Reviewed and addressed accordingly.' : null,
                'is_anonymous' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedResignations(): void
    {
        $lastEmployeeId = end(StaffSeeder::$employeeIds);

        DB::table('resignations')->insert([
            'employee_user_id' => $lastEmployeeId,
            'supervisor_user_id' => StaffSeeder::$employeeSupervisor[$lastEmployeeId],
            'reason' => 'Pursuing a new opportunity in a different field.',
            'notice_date' => now()->toDateString(),
            'last_working_date' => now()->addDays(30)->toDateString(),
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function seedNotifications(): void
    {
        $samples = [
            ['title' => 'New task assigned', 'body' => 'You have a new task to complete.', 'type' => 'task'],
            ['title' => 'Attendance reminder', 'body' => 'Do not forget to check in today.', 'type' => 'attendance'],
            ['title' => 'Payroll ready', 'body' => 'Your latest payslip is now available.', 'type' => 'payroll'],
        ];
        $companyId = OrganizationSeeder::$companyId;
        $allUsers = array_merge(array_values(OrganizationSeeder::$managerIds), StaffSeeder::$employeeIds);

        foreach ($allUsers as $userId) {
            foreach ($samples as $n) {
                DB::table('notifications')->insert([
                    'company_id' => $companyId, 'user_id' => $userId,
                    'title' => $n['title'], 'body' => $n['body'], 'type' => $n['type'],
                    'is_read' => rand(0, 1) === 1, 'created_at' => now()->subHours(rand(1, 72)),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}