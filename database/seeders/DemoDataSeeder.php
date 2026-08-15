<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    private string $pwHash;
    private int $ownerId;
    private string $ownerPhone;

    public function run(): void
    {
        $this->pwHash = Hash::make(Str::random(32));

        DB::transaction(function () {
            $this->seedSuperAdmin();
            $planIds = $this->seedSubscriptionPlans();
            $companyId = $this->seedCompanyAndOwner($planIds[1]);
            [$branch1, $branch2] = $this->seedBranches($companyId);
            $managers = $this->seedManagers($companyId, [$branch1, $branch2]);
            $departments = $this->seedDepartments([$branch1, $branch2]);
            $supervisors = $this->seedSupervisors([$branch1, $branch2]);
            $leaveTypeIds = $this->seedLeaveTypes($companyId);
            $exceptionTypeIds = $this->seedExceptionTypes();
            [$employees, $employeeSupervisors] = $this->seedEmployees($companyId, $departments, $supervisors);

            $this->seedLeaveBalances($employees, $leaveTypeIds);
            $this->seedLeaveRequests($companyId, $employees, $leaveTypeIds);
            $this->seedExceptionRequests($companyId, $employees, $exceptionTypeIds);
            $this->seedAttendance($companyId, $employees, [$branch1, $branch2]);
            $this->seedTasks($companyId, $employees, $supervisors);
            $this->seedPayroll($companyId, $employees);
            $this->seedWorkshops($companyId, [$branch1, $branch2], $managers, $employees);
            $this->seedAnnouncements($companyId, [$branch1, $branch2], $managers);
            $this->seedComplaints($companyId, $employees, $departments);
            $this->seedResignations($employees, $employeeSupervisors);
            $this->seedNotifications($companyId, array_merge([$this->ownerId], $employees));
        });

        $this->command->warn('=====================================================');
        $this->command->warn('Demo data seeded successfully!');
        $this->command->warn("Owner: {$this->ownerPhone}");
        $this->command->warn('Super Admin: admin@test.com / Password123');
        $this->command->warn('Managers, Supervisors, Employees phones printed above.');
        $this->command->warn('=====================================================');
    }

    // ============ Super Admin ============

    private function seedSuperAdmin(): void
    {
        if (DB::table('users')->where('email', 'admin@test.com')->exists()) {
            return;
        }

        $adminId = DB::table('users')->insertGetId([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('Password123'),
            'user_type' => 'super_admin',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_profiles')->insert([
            'user_id' => $adminId,
            'full_name' => 'Platform Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ============ Companies / Plans / Owner ============

    private function seedSubscriptionPlans(): array
    {
        $plans = [
            ['name' => 'Basic', 'price' => 50, 'billing_cycle' => 'monthly', 'max_branches' => 1, 'max_employees' => 10, 'features' => json_encode(['attendance', 'tasks'])],
            ['name' => 'Standard', 'price' => 125, 'billing_cycle' => 'monthly', 'max_branches' => 5, 'max_employees' => 50, 'features' => json_encode(['attendance', 'tasks', 'payroll', 'reports'])],
            ['name' => 'Premium', 'price' => 250, 'billing_cycle' => 'monthly', 'max_branches' => null, 'max_employees' => null, 'features' => json_encode(['attendance', 'tasks', 'payroll', 'reports', 'workshops', 'complaints'])],
        ];

        $ids = [];
        foreach ($plans as $i => $plan) {
            $ids[$i] = DB::table('subscription_plans')->insertGetId(array_merge($plan, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        return $ids;
    }

    private function seedCompanyAndOwner(int $standardPlanId): int
    {
        $this->ownerPhone = '0981936633';

        $this->ownerId = DB::table('users')->insertGetId([
            'phone' => $this->ownerPhone,
            'email' => 'owner@novaretail.test',
            'password_hash' => $this->pwHash,
            'user_type' => 'owner',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_profiles')->insert([
            'user_id' => $this->ownerId,
            'full_name' => 'James Anderson',
            'national_id' => 'N100000001',
            'date_of_birth' => '1978-04-12',
            'gender' => 'male',
            'address' => '12 King Street, Amman',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $companyId = DB::table('companies')->insertGetId([
            'owner_user_id' => $this->ownerId,
            'name' => 'Nova Retail Group',
            'status' => 'active',
            'industry' => 'Retail',
            'website' => 'https://novaretail.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $this->ownerId)->update(['company_id' => $companyId]);

        DB::table('company_subscriptions')->insert([
            'company_id' => $companyId,
            'plan_id' => $standardPlanId,
            'start_date' => now()->subMonths(3)->toDateString(),
            'end_date' => now()->addMonths(9)->toDateString(),
            'auto_renew' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $companyId;
    }

    // ============ Branches ============

    private function seedBranches(int $companyId): array
    {
        $b1 = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Downtown Branch',
            'location' => 'Downtown, Amman',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $b2 = DB::table('branches')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Airport Road Branch',
            'location' => 'Airport Road, Amman',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$b1, $b2];
    }

    // ============ Managers ============

    private function seedManagers(int $companyId, array $branches): array
    {
        $names = ['Michael Reed', 'Sarah Collins'];
        $ids = [];

        foreach ($branches as $i => $branchId) {
            $phone = '0932556713' . ($i + 1);

            $userId = DB::table('users')->insertGetId([
                'phone' => $phone,
                'password_hash' => $this->pwHash,
                'user_type' => 'manager',
                'status' => 'active',
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('user_profiles')->insert([
                'user_id' => $userId,
                'full_name' => $names[$i],
                'national_id' => 'N10001000' . ($i + 1),
                'date_of_birth' => '1985-06-15',
                'gender' => $i === 0 ? 'male' : 'female',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $ids[$branchId] = $userId;
            $this->command->info("Manager ({$names[$i]}): {$phone}");
        }

        return $ids;
    }

    // ============ Departments ============

    private function seedDepartments(array $branches): array
    {
        $deptNames = ['Sales', 'Operations'];
        $result = [];

        foreach ($branches as $branchId) {
            foreach ($deptNames as $name) {
                $result[$branchId][] = DB::table('departments')->insertGetId([
                    'branch_id' => $branchId,
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $result;
    }

    // ============ Supervisors ============

    private function seedSupervisors(array $branches): array
    {
        $names = ['James Carter', 'Emily Bennett', 'David Foster', 'Olivia Martin'];
        $result = [];
        $counter = 1;

        foreach ($branches as $branchId) {
            for ($i = 0; $i < 2; $i++) {
                $phone = '0994469119' . str_pad($counter, 2, '0', STR_PAD_LEFT);

                $userId = DB::table('users')->insertGetId([
                    'phone' => $phone,
                    'password_hash' => $this->pwHash,
                    'user_type' => 'supervisor',
                    'status' => 'active',
                    'branch_id' => $branchId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('user_profiles')->insert([
                    'user_id' => $userId,
                    'full_name' => $names[$counter - 1],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $result[$branchId][] = $userId;
                $this->command->info("Supervisor ({$names[$counter - 1]}): {$phone}");
                $counter++;
            }
        }

        return $result;
    }

    // ============ Leave Types ============

    private function seedLeaveTypes(int $companyId): array
    {
        $types = [
            ['name' => 'Annual Leave', 'code' => 'annual', 'is_paid' => true, 'max_days_per_year' => 21, 'requires_attachment' => false],
            ['name' => 'Sick Leave', 'code' => 'sick', 'is_paid' => true, 'max_days_per_year' => 14, 'requires_attachment' => true],
            ['name' => 'Emergency Leave', 'code' => 'emergency', 'is_paid' => true, 'max_days_per_year' => 5, 'requires_attachment' => false],
            ['name' => 'Unpaid Leave', 'code' => 'unpaid', 'is_paid' => false, 'max_days_per_year' => 30, 'requires_attachment' => false],
        ];

        $ids = [];
        foreach ($types as $t) {
            $ids[$t['code']] = DB::table('leave_types')->insertGetId(array_merge($t, [
                'company_id' => $companyId,
                'description' => $t['name'] . ' policy',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        return $ids;
    }

    // ============ Exception Types ============

    private function seedExceptionTypes(): array
    {
        $types = [
            ['name' => 'Justified Absence', 'slug' => 'justified_absence'],
            ['name' => 'Deduction Reconsideration', 'slug' => 'deduction_reconsideration'],
            ['name' => 'Salary Addition', 'slug' => 'salary_addition'],
            ['name' => 'Exceptional Leave', 'slug' => 'exceptional_leave'],
        ];

        $ids = [];
        foreach ($types as $t) {
            $ids[] = DB::table('exception_types')->insertGetId(array_merge($t, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        return $ids;
    }

    // ============ Employees ============

    private function seedEmployees(int $companyId, array $departments, array $supervisors): array
    {
        $firstNames = ['Daniel', 'Sophia', 'Ethan', 'Grace', 'Lucas', 'Chloe', 'Mason', 'Ava', 'Noah', 'Mia', 'Liam', 'Zoe'];
        $lastNames = ['Foster', 'Bennett', 'Carter', 'Hughes', 'Parker', 'Reed', 'Morgan', 'Bailey', 'Cooper', 'Ward', 'Ellis', 'Hayes'];
        $jobTitles = ['Sales Associate', 'Cashier', 'Warehouse Clerk', 'Customer Support', 'Stock Manager', 'Team Lead'];

        $employees = [];
        $employeeSupervisors = [];
        $counter = 1;

        foreach ($departments as $branchId => $deptIds) {
            $branchSupervisors = $supervisors[$branchId];

            foreach ($deptIds as $deptIndex => $deptId) {
                for ($i = 0; $i < 3; $i++) {
                    $idx = $counter - 1;
                    $phone = '0988232386' . str_pad($counter, 2, '0', STR_PAD_LEFT);
                    $supervisorId = $branchSupervisors[$deptIndex % count($branchSupervisors)];

                    $userId = DB::table('users')->insertGetId([
                        'phone' => $phone,
                        'password_hash' => $this->pwHash,
                        'user_type' => 'employee',
                        'status' => 'active',
                        'branch_id' => $branchId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $fullName = $firstNames[$idx % count($firstNames)] . ' ' . $lastNames[$idx % count($lastNames)];

                    DB::table('user_profiles')->insert([
                        'user_id' => $userId,
                        'full_name' => $fullName,
                        'national_id' => 'N2000' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                        'date_of_birth' => Carbon::now()->subYears(rand(22, 40))->toDateString(),
                        'gender' => $idx % 2 === 0 ? 'male' : 'female',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('employee_details')->insert([
                        'user_id' => $userId,
                        'department_id' => $deptId,
                        'supervisor_id' => $supervisorId,
                        'job_title' => $jobTitles[$idx % count($jobTitles)],
                        'contract_type' => $idx % 4 === 0 ? 'part_time' : 'full_time',
                        'basic_salary' => rand(350, 900),
                        'employment_status' => 'active',
                        'hire_date' => Carbon::now()->subMonths(rand(1, 30))->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $employees[] = $userId;
                    $employeeSupervisors[$userId] = $supervisorId;
                    $counter++;
                }
            }
        }

        $this->command->info(count($employees) . ' employees created (phones 0988232386-0994469119).');

        return [$employees, $employeeSupervisors];
    }

    // ============ Leave Balances ============

    private function seedLeaveBalances(array $employees, array $leaveTypeIds): void
    {
        $year = now()->year;

        foreach ($employees as $employeeId) {
            foreach ($leaveTypeIds as $code => $typeId) {
                $maxDays = match ($code) {
                    'annual' => 21, 'sick' => 14, 'emergency' => 5, default => 30,
                };

                DB::table('leave_balances')->insert([
                    'employee_user_id' => $employeeId,
                    'leave_type_id' => $typeId,
                    'year' => $year,
                    'remaining_days' => rand(intval($maxDays * 0.4), $maxDays),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    // ============ Leave Requests ============

    private function seedLeaveRequests(int $companyId, array $employees, array $leaveTypeIds): void
    {
        $reasons = [
            'Family event out of town.',
            'Doctor appointment and recovery.',
            'Personal matters to attend to.',
        ];
        $statuses = ['pending_manager', 'approved', 'rejected'];

        foreach (array_slice($employees, 0, 6) as $i => $employeeId) {
            $start = Carbon::now()->addDays(rand(-20, 20));

            DB::table('leave_requests')->insert([
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'leave_type_id' => array_values($leaveTypeIds)[$i % count($leaveTypeIds)],
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays(rand(1, 3))->toDateString(),
                'reason' => $reasons[$i % count($reasons)],
                'status' => $statuses[$i % count($statuses)],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // ============ Exception Requests ============

    private function seedExceptionRequests(int $companyId, array $employees, array $exceptionTypeIds): void
    {
        $reasons = [
            'Was stuck in traffic due to road closure.',
            'Requesting review of last deduction, it was recorded by mistake.',
            'Worked extra hours covering a colleague shift.',
            'Need one day off for a family emergency.',
        ];
        $statuses = ['pending', 'approved', 'rejected'];

        foreach (array_slice($employees, 0, 8) as $i => $employeeId) {
            DB::table('exception_requests')->insert([
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'exception_type_id' => $exceptionTypeIds[$i % count($exceptionTypeIds)],
                'request_date' => Carbon::now()->subDays(rand(0, 10))->toDateString(),
                'reason' => $reasons[$i % count($reasons)],
                'status' => $statuses[$i % count($statuses)],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // ============ Attendance ============

    private function seedAttendance(int $companyId, array $employees, array $branches): void
    {
        $employeesByBranch = DB::table('users')->whereIn('id', $employees)->pluck('branch_id', 'id');

        foreach ($employees as $employeeId) {
            $branchId = $employeesByBranch[$employeeId];

            for ($d = 13; $d >= 0; $d--) {
                $date = Carbon::now()->subDays($d);

                if ($date->isFriday()) {
                    continue;
                }

                $roll = rand(1, 100);
                if ($roll <= 10) {
                    continue;
                }

                $status = $roll <= 25 ? 'late' : 'present';
                $checkIn = $date->copy()->setTime($status === 'late' ? rand(8, 9) : rand(7, 8), rand(0, 59));
                $checkOut = $checkIn->copy()->addHours(8)->addMinutes(rand(-15, 30));

                DB::table('attendance_logs')->insert([
                    'company_id' => $companyId,
                    'employee_user_id' => $employeeId,
                    'branch_id' => $branchId,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'work_hours' => round($checkIn->diffInMinutes($checkOut) / 60, 2),
                    'type' => 'qr',
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    // ============ Tasks ============

    private function seedTasks(int $companyId, array $employees, array $supervisors): void
    {
        $titles = [
            'Restock shelves in aisle 3', 'Prepare weekly inventory report',
            'Clean and organize storage room', 'Assist with customer complaints log',
            'Update product price tags', 'Train new team member on POS system',
        ];
        $priorities = ['low', 'medium', 'high'];
        $statuses = ['pending', 'in_progress', 'completed'];

        $allSupervisors = array_merge(...array_values($supervisors));

        foreach (array_slice($employees, 0, 10) as $i => $employeeId) {
            DB::table('tasks')->insert([
                'company_id' => $companyId,
                'employee_user_id' => $employeeId,
                'supervisor_user_id' => $allSupervisors[$i % count($allSupervisors)],
                'title' => $titles[$i % count($titles)],
                'description' => 'Please complete this task before the due date and report back.',
                'type' => 'ad_hoc',
                'priority' => $priorities[$i % 3],
                'due_date' => Carbon::now()->addDays(rand(-3, 7)),
                'status' => $statuses[$i % 3],
                'completed_at' => $i % 3 === 2 ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // ============ Payroll ============

    private function seedPayroll(int $companyId, array $employees): void
    {
        $periodId = DB::table('payroll_periods')->insertGetId([
            'company_id' => $companyId,
            'month' => now()->month,
            'year' => now()->year,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'status' => 'calculated',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $basicSalaries = DB::table('employee_details')
            ->whereIn('user_id', $employees)
            ->pluck('basic_salary', 'user_id');

        foreach ($employees as $employeeId) {
            $base = (float) ($basicSalaries[$employeeId] ?? 500);
            $bonus = rand(0, 1) ? rand(20, 80) : 0;
            $overtime = rand(0, 1) ? rand(15, 60) : 0;
            $deduction = rand(0, 1) ? rand(10, 40) : 0;

            $gross = $base + $bonus + $overtime;
            $net = $gross - $deduction;

            $recordId = DB::table('payroll_records')->insertGetId([
                'employee_user_id' => $employeeId,
                'period_id' => $periodId,
                'gross_salary' => $gross,
                'total_deductions' => $deduction,
                'total_bonuses' => $bonus,
                'net_salary' => $net,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $components = [
                ['name' => 'Base Salary', 'component_type' => 'base_salary', 'amount' => $base],
            ];
            if ($bonus > 0) $components[] = ['name' => 'Performance Bonus', 'component_type' => 'bonus', 'amount' => $bonus];
            if ($overtime > 0) $components[] = ['name' => 'Overtime', 'component_type' => 'overtime', 'amount' => $overtime];
            if ($deduction > 0) $components[] = ['name' => 'Late Deduction', 'component_type' => 'deduction', 'amount' => $deduction];

            foreach ($components as $c) {
                DB::table('payroll_record_details')->insert(array_merge($c, [
                    'record_id' => $recordId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    // ============ Workshops ============

    private function seedWorkshops(int $companyId, array $branches, array $managers, array $employees): void
    {
        $workshops = [
            ['title' => 'Customer Service Excellence', 'location' => 'Main Hall'],
            ['title' => 'POS System Update Training', 'location' => 'Training Room A'],
            ['title' => 'Workplace Safety Refresher', 'location' => 'Conference Room'],
        ];

        foreach ($workshops as $i => $w) {
            $branchId = $branches[$i % count($branches)];
            $start = Carbon::now()->addDays(rand(3, 20))->setTime(10, 0);

            $workshopId = DB::table('workshops')->insertGetId([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => $managers[$branchId],
                'title' => $w['title'],
                'description' => 'A short internal workshop for staff development.',
                'location' => $w['location'],
                'start_date' => $start,
                'end_date' => $start->copy()->addHours(2),
                'capacity' => 15,
                'audience' => 'employee',
                'status' => 'upcoming',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (array_slice($employees, $i * 2, 3) as $employeeId) {
                DB::table('workshop_attendees')->insert([
                    'workshop_id' => $workshopId,
                    'employee_user_id' => $employeeId,
                    'status' => 'registered',
                    'registered_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    // ============ Announcements ============

    private function seedAnnouncements(int $companyId, array $branches, array $managers): void
    {
        $announcements = [
            ['title' => 'Holiday Schedule Update', 'content' => 'Please note the updated holiday schedule for next month, available on the notice board.'],
            ['title' => 'New Break Room Opening', 'content' => 'The renovated break room will open next week for all staff.'],
        ];

        foreach ($announcements as $i => $a) {
            $branchId = $branches[$i % count($branches)];

            DB::table('announcements')->insert([
                'company_id' => $companyId,
                'created_by' => $managers[$branchId],
                'title' => $a['title'],
                'content' => $a['content'],
                'target_type' => 'branch',
                'target_id' => $branchId,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(30)->toDateString(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // ============ Complaints ============

    private function seedComplaints(int $companyId, array $employees, array $departments): void
    {
        $firstDeptId = array_values($departments)[0][0];

        $complaints = [
            ['subject' => 'Break room AC not working', 'description' => 'The break room air conditioning has not been working for a week.'],
            ['subject' => 'Shift scheduling fairness', 'description' => 'I would like to raise a concern regarding shift scheduling fairness.'],
        ];

        foreach ($complaints as $i => $c) {
            DB::table('complaints')->insert([
                'company_id' => $companyId,
                'user_id' => $employees[$i],
                'department_id' => $firstDeptId,
                'subject' => $c['subject'],
                'description' => $c['description'],
                'status' => $i === 0 ? 'open' : 'resolved',
                'response' => $i === 0 ? null : 'Reviewed and adjusted the schedule accordingly.',
                'is_anonymous' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // ============ Resignations ============

    private function seedResignations(array $employees, array $employeeSupervisors): void
    {
        $lastEmployeeId = $employees[count($employees) - 1];

        DB::table('resignations')->insert([
            'employee_user_id' => $lastEmployeeId,
            'supervisor_user_id' => $employeeSupervisors[$lastEmployeeId],
            'reason' => 'Pursuing a new opportunity in a different field.',
            'notice_date' => now()->toDateString(),
            'last_working_date' => now()->addDays(30)->toDateString(),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ============ Notifications ============

    private function seedNotifications(int $companyId, array $userIds): void
    {
        $samples = [
            ['title' => 'New task assigned', 'body' => 'You have a new task to complete.', 'type' => 'task'],
            ['title' => 'Attendance reminder', 'body' => 'Do not forget to check in today.', 'type' => 'attendance'],
            ['title' => 'Payroll ready', 'body' => 'Your latest payslip is now available.', 'type' => 'payroll'],
        ];

        foreach ($userIds as $userId) {
            foreach ($samples as $n) {
                DB::table('notifications')->insert([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'title' => $n['title'],
                    'body' => $n['body'],
                    'type' => $n['type'],
                    'is_read' => rand(0, 1) === 1,
                    'created_at' => now()->subHours(rand(1, 72)),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}