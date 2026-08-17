<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StaffSeeder extends Seeder
{
    public static array $employeeIds = [];         // flat list of all employee user_ids
    public static array $employeeSupervisor = [];   // user_id => supervisor_user_id
    public static array $employeeBranch = [];       // user_id => branch_id
    public static array $employeeDetailId = [];     // user_id => employee_details.id
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

        // ---- Employees (24 total: 3 per department across 8 departments) ----
        $firstNames = ['Daniel', 'Sophia', 'Ethan', 'Grace', 'Lucas', 'Chloe', 'Mason', 'Ava', 'Noah', 'Mia',
                        'Liam', 'Zoe', 'Ryan', 'Isla', 'Owen', 'Nora', 'Leo', 'Ella', 'Jack', 'Amelia',
                        'Henry', 'Layla', 'Aiden', 'Ruby'];
        $lastNames = ['Foster', 'Bennett', 'Carter', 'Hughes', 'Parker', 'Reed', 'Morgan', 'Bailey', 'Cooper', 'Ward',
                      'Ellis', 'Hayes', 'Turner', 'Brooks', 'Powell', 'Fox', 'Simmons', 'Barnes', 'Grant', 'Wells',
                      'Sanders', 'Price', 'Hunt', 'Stone'];
        $jobTitlesByDept = [
            'IT' => ['IT Support Specialist', 'Systems Administrator', 'Network Technician'],
            'HR' => ['HR Coordinator', 'Recruitment Specialist', 'Payroll Officer'],
            'Sales' => ['Sales Associate', 'Account Executive', 'Customer Relations Officer'],
            'Operations' => ['Warehouse Clerk', 'Logistics Coordinator', 'Stock Manager'],
        ];
        $deptNames = ['IT', 'HR', 'Sales', 'Operations'];

        $counter = 1;
        foreach (OrganizationSeeder::$branchIds as $bIndex => $branchId) {
            foreach (OrganizationSeeder::$departmentIds[$branchId] as $dIndex => $deptId) {
                $deptName = $deptNames[$dIndex];
                $supervisorId = OrganizationSeeder::$supervisorIds[$branchId][intdiv($dIndex, 2)];

                for ($i = 0; $i < 3; $i++) {
                    $idx = $counter - 1;
                    $phone = '09000003' . str_pad($counter, 2, '0', STR_PAD_LEFT);

                    $userId = DB::table('users')->insertGetId([
                        'phone' => $phone, 'password_hash' => $pwHash, 'user_type' => 'employee',
                        'status' => 'active', 'branch_id' => $branchId, 'created_at' => now(), 'updated_at' => now(),
                    ]);

                    $fullName = $firstNames[$idx % count($firstNames)] . ' ' . $lastNames[$idx % count($lastNames)];

                    DB::table('user_profiles')->insert([
                        'user_id' => $userId, 'full_name' => $fullName,
                        'national_id' => 'N2000' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                        'date_of_birth' => Carbon::now()->subYears(rand(22, 45))->toDateString(),
                        'gender' => $idx % 2 === 0 ? 'male' : 'female',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);

                    $detailId = DB::table('employee_details')->insertGetId([
                        'user_id' => $userId, 'department_id' => $deptId, 'supervisor_id' => $supervisorId,
                        'job_title' => $jobTitlesByDept[$deptName][array_rand($jobTitlesByDept[$deptName])],
                        'contract_type' => rand(1, 10) > 8 ? 'part_time' : 'full_time',
                        'basic_salary' => rand(400, 1100),
                        'employment_status' => 'active',
                        'hire_date' => Carbon::now()->subMonths(rand(2, 36))->toDateString(),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);

                    self::$employeeIds[] = $userId;
                    self::$employeeSupervisor[$userId] = $supervisorId;
                    self::$employeeBranch[$userId] = $branchId;
                    self::$employeeDetailId[$userId] = $detailId;
                    $counter++;
                }
            }
        }

        $this->command->info(count(self::$employeeIds) . " employees created (phones 0900000301-09000003" . ($counter - 1) . ")");
    }
}