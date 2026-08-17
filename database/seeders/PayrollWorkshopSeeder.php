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

    // ---- Payroll: last 2 months, one approved+paid, one calculated ----
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
            ['title' => 'Workplace Safety Refresher', 'location' => 'Conference Room', 'days_from_now' => 25],
            ['title' => 'Leadership Skills for Team Leads', 'location' => 'Main Hall', 'days_from_now' => -5], // past, completed
        ];
        $companyId = OrganizationSeeder::$companyId;

        foreach ($workshops as $i => $w) {
            $branchId = OrganizationSeeder::$branchIds[$i % 2];
            $start = Carbon::now()->addDays($w['days_from_now'])->setTime(10, 0);
            $isPast = $w['days_from_now'] < 0;

            $workshopId = DB::table('workshops')->insertGetId([
                'company_id' => $companyId, 'branch_id' => $branchId,
                'created_by' => OrganizationSeeder::$managerIds[$branchId],
                'title' => $w['title'], 'description' => 'Internal staff development workshop.',
                'location' => $w['location'], 'start_date' => $start, 'end_date' => $start->copy()->addHours(2),
                'capacity' => 15, 'audience' => 'employee', 'status' => $isPast ? 'completed' : 'upcoming',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $attendees = collect(StaffSeeder::$employeeIds)->filter(fn($id) => StaffSeeder::$employeeBranch[$id] === $branchId)->random(3);
            foreach ($attendees as $employeeId) {
                DB::table('workshop_attendees')->insert([
                    'workshop_id' => $workshopId, 'employee_user_id' => $employeeId,
                    'status' => $isPast ? 'attended' : 'registered', 'registered_at' => now(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedAnnouncements(): void
    {
        $announcements = [
            ['title' => 'Holiday Schedule Update', 'content' => 'Please note the updated holiday schedule for next month, available on the notice board.'],
            ['title' => 'New Break Room Opening', 'content' => 'The renovated break room will open next week for all staff.'],
            ['title' => 'Quarterly Town Hall', 'content' => 'Join us for the quarterly town hall meeting to discuss company updates.'],
            ['title' => 'Parking Lot Maintenance', 'content' => 'The staff parking lot will be under maintenance this weekend.'],
        ];
        $companyId = OrganizationSeeder::$companyId;

        foreach ($announcements as $i => $a) {
            $branchId = OrganizationSeeder::$branchIds[$i % 2];
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
            ['subject' => 'Locker room cleanliness', 'description' => 'The locker room needs more frequent cleaning.', 'status' => 'in_review'],
            ['subject' => 'Overtime pay discrepancy', 'description' => 'Overtime hours for last month do not match my records.', 'status' => 'closed'],
        ];
        $companyId = OrganizationSeeder::$companyId;
        $depts = DB::table('departments')->pluck('id');

        foreach ($complaints as $i => $c) {
            DB::table('complaints')->insert([
                'company_id' => $companyId, 'user_id' => StaffSeeder::$employeeIds[$i],
                'department_id' => $depts[$i % count($depts)],
                'subject' => $c['subject'], 'description' => $c['description'], 'status' => $c['status'],
                'response' => in_array($c['status'], ['resolved', 'closed']) ? 'Reviewed and addressed accordingly.' : null,
                'is_anonymous' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedResignations(): void
    {
        $resigningEmployees = collect(StaffSeeder::$employeeIds)->random(2);
        $statuses = ['pending', 'approved'];

        foreach ($resigningEmployees as $i => $employeeId) {
            DB::table('resignations')->insert([
                'employee_user_id' => $employeeId,
                'supervisor_user_id' => StaffSeeder::$employeeSupervisor[$employeeId],
                'reason' => 'Pursuing a new opportunity in a different field.',
                'notice_date' => now()->toDateString(),
                'last_working_date' => now()->addDays(30)->toDateString(),
                'status' => $statuses[$i % 2],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function seedNotifications(): void
    {
        $samples = [
            ['title' => 'New task assigned', 'body' => 'You have a new task to complete.', 'type' => 'task'],
            ['title' => 'Attendance reminder', 'body' => 'Do not forget to check in today.', 'type' => 'attendance'],
            ['title' => 'Payroll ready', 'body' => 'Your latest payslip is now available.', 'type' => 'payroll'],
            ['title' => 'New announcement', 'body' => 'A new company announcement was posted.', 'type' => 'announcement'],
        ];
        $companyId = OrganizationSeeder::$companyId;

        $allUsers = array_merge(
            array_values(OrganizationSeeder::$managerIds),
            StaffSeeder::$employeeIds
        );

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