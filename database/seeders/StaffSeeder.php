<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StaffSeeder extends Seeder
{
    public static array $employeeIds = [];
    public static array $employeeSupervisor = [];
    public static array $employeeBranch = [];
    public static array $employeeDetailId = [];
    public static array $leaveTypeIds = [];
    public static array $exceptionTypeIds = [];

    public function run(): void
    {
        $companyId = OrganizationSeeder::$companyId;
        $pwHash = Hash::make(Str::random(32));

        // ---- Leave Types ----
        $leaveTypes = [
            ['name' => 'Annual Leave', 'code' => 'annual', 'is_paid' => true, 'max_days_per_year' => 21, 'requires_attachment' => false],
            ['name' => 'Sick Leave', 'code' => 'sick', 'is_paid' => true, 'max_days_per_year' => 14, 'requires_attachment' => true],
            ['name' => 'Emergency Leave', 'code' => 'emergency', 'is_paid' => true, 'max_days_per_year' => 5, 'requires_attachment' => false],
            ['name' => 'Unpaid Leave', 'code' => 'unpaid', 'is_paid' => false, 'max_days_per_year' => 30, 'requires_attachment' => false],
        ];
        foreach ($leaveTypes as $t) {
            self::$leaveTypeIds[$t['code']] = DB::table('leave_types')->insertGetId(array_merge($t, [
                'company_id' => $companyId, 'description' => $t['name'] . ' policy',
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]));
        }

        // ---- Exception Types ----
        $exceptionTypes = [
            ['name' => 'Justified Absence', 'slug' => 'justified_absence'],
            ['name' => 'Deduction Reconsideration', 'slug' => 'deduction_reconsideration'],
            ['name' => 'Salary Addition', 'slug' => 'salary_addition'],
            ['name' => 'Exceptional Leave', 'slug' => 'exceptional_leave'],
        ];
        foreach ($exceptionTypes as $t) {
            self::$exceptionTypeIds[] = DB::table('exception_types')->insertGetId(array_merge($t, [
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]));
        }

        // ============================================================
        // المجموعة 1: الـ 6 موظفين بأرقام حقيقية (مرتبطين بالمشرفين الحقيقيين)
        // ============================================================
        $realBranchId = OrganizationSeeder::$branchIds[0];
        [$realSupervisor1, $realSupervisor2] = OrganizationSeeder::$supervisorIds[$realBranchId];

        $realEmployeeGroups = [
            $realSupervisor1 => [
                ['phone' => '0991236665', 'name' => 'Daniel Foster'],
                ['phone' => '0957868203', 'name' => 'Sophia Bennett'],
                ['phone' => '0994469119', 'name' => 'Ethan Carter'],
            ],
            $realSupervisor2 => [
                ['phone' => '0991698782', 'name' => 'Grace Hughes'],
                ['phone' => '0992728926', 'name' => 'Lucas Parker'],
                ['phone' => '0998876332', 'name' => 'Chloe Reed'],
            ],
        ];

        $jobTitles = ['Sales Associate', 'Customer Support Officer', 'Warehouse Clerk', 'IT Support Specialist', 'HR Coordinator', 'Stock Manager'];
        $counter = 1;

        foreach ($realEmployeeGroups as $supervisorId => $employees) {
            $deptId = OrganizationSeeder::$supervisorDepartment[$supervisorId];

            foreach ($employees as $idx => $emp) {
                $userId = DB::table('users')->insertGetId([
                    'phone' => $emp['phone'], 'password_hash' => $pwHash, 'user_type' => 'employee',
                    'status' => 'active', 'branch_id' => $realBranchId, 'created_at' => now(), 'updated_at' => now(),
                ]);

                DB::table('user_profiles')->insert([
                    'user_id' => $userId, 'full_name' => $emp['name'],
                    'national_id' => 'N2000' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                    'date_of_birth' => Carbon::now()->subYears(rand(22, 40))->toDateString(),
                    'gender' => $counter % 2 === 0 ? 'female' : 'male',
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                $detailId = DB::table('employee_details')->insertGetId([
                    'user_id' => $userId, 'department_id' => $deptId, 'supervisor_id' => $supervisorId,
                    'job_title' => $jobTitles[$idx % count($jobTitles)],
                    'contract_type' => 'full_time', 'basic_salary' => rand(400, 900),
                    'employment_status' => 'active', 'hire_date' => Carbon::now()->subMonths(rand(2, 24))->toDateString(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                self::$employeeIds[] = $userId;
                self::$employeeSupervisor[$userId] = $supervisorId;
                self::$employeeBranch[$userId] = $realBranchId;
                self::$employeeDetailId[$userId] = $detailId;

                $this->command->info("Real Employee ({$emp['name']}): {$emp['phone']} — Supervisor: {$supervisorId}");
                $counter++;
            }
        }

        // ============================================================
        // المجموعة 2: باقي الـ 18 موظف — أرقام عشوائية، موزّعين على باقي الأقسام/المشرفين
        // (يشمل باقي أقسام الفرع الأول، وكل أقسام الفرع التاني)
        // ============================================================
        $firstNames = ['Mason', 'Ava', 'Noah', 'Mia', 'Liam', 'Zoe', 'Ryan', 'Isla', 'Owen', 'Nora',
                        'Leo', 'Ella', 'Jack', 'Amelia', 'Henry', 'Layla', 'Aiden', 'Ruby'];
        $lastNames = ['Turner', 'Brooks', 'Powell', 'Fox', 'Simmons', 'Barnes', 'Grant', 'Wells',
                      'Sanders', 'Price', 'Hunt', 'Stone', 'Reed', 'Morgan', 'Bailey', 'Cooper', 'Ward', 'Ellis'];

        $remainingAssignments = []; // [branchId => [[deptId, supervisorId], ...]]

        foreach (OrganizationSeeder::$departmentIds as $branchId => $deptIds) {
            foreach ($deptIds as $deptId) {
                $supervisorId = DB::table('departments')->where('id', $deptId)->value('supervisor_user_id');

                // استبعد الأقسام اللي أصلًا اتغطّت بالموظفين الحقيقيين (أقسام المشرفين 1 و2)
                if (in_array($supervisorId, [$realSupervisor1, $realSupervisor2])) {
                    continue;
                }

                $remainingAssignments[] = [$branchId, $deptId, $supervisorId];
            }
        }

        $randomCounter = 1;
        for ($i = 0; $i < 18; $i++) {
            [$branchId, $deptId, $supervisorId] = $remainingAssignments[$i % count($remainingAssignments)];

            $phone = '09' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

            $userId = DB::table('users')->insertGetId([
                'phone' => $phone, 'password_hash' => $pwHash, 'user_type' => 'employee',
                'status' => 'active', 'branch_id' => $branchId, 'created_at' => now(), 'updated_at' => now(),
            ]);

            $fullName = $firstNames[$i % count($firstNames)] . ' ' . $lastNames[$i % count($lastNames)];

            DB::table('user_profiles')->insert([
                'user_id' => $userId, 'full_name' => $fullName,
                'national_id' => 'N3000' . str_pad($randomCounter, 4, '0', STR_PAD_LEFT),
                'date_of_birth' => Carbon::now()->subYears(rand(22, 45))->toDateString(),
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $detailId = DB::table('employee_details')->insertGetId([
                'user_id' => $userId, 'department_id' => $deptId, 'supervisor_id' => $supervisorId,
                'job_title' => $jobTitles[$i % count($jobTitles)],
                'contract_type' => rand(1, 10) > 8 ? 'part_time' : 'full_time',
                'basic_salary' => rand(400, 1100),
                'employment_status' => 'active', 'hire_date' => Carbon::now()->subMonths(rand(2, 36))->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            self::$employeeIds[] = $userId;
            self::$employeeSupervisor[$userId] = $supervisorId;
            self::$employeeBranch[$userId] = $branchId;
            self::$employeeDetailId[$userId] = $detailId;

            $randomCounter++;
        }

        $this->command->info(count(self::$employeeIds) . ' total employees created (6 real + 18 random).');
    }
}