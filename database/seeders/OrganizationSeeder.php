<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    public static array $branchIds = [];
    public static array $departmentIds = []; // [branch_id => [dept_id, ...]]
    public static array $managerIds = [];    // [branch_id => manager_user_id]
    public static array $supervisorIds = []; // [branch_id => [supervisor_user_id, ...]]
    public static array $supervisorDepartment = []; // [supervisor_user_id => primary_department_id]
    public static int $companyId;

    public function run(): void
    {
        $pwHash = Hash::make(Str::random(32));

        // ---- Owner + Company ----
        $ownerId = DB::table('users')->insertGetId([
            'phone' => '0939624070', 'email' => 'owner@novaretail.test', 'password_hash' => $pwHash,
            'user_type' => 'owner', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('user_profiles')->insert([
            'user_id' => $ownerId, 'full_name' => 'James Anderson',
            'national_id' => 'N100000001', 'date_of_birth' => '1978-04-12', 'gender' => 'male',
            'address' => '12 King Street, Amman', 'created_at' => now(), 'updated_at' => now(),
        ]);

        self::$companyId = DB::table('companies')->insertGetId([
            'owner_user_id' => $ownerId, 'name' => 'Nova Retail Group', 'status' => 'active',
            'industry' => 'Retail', 'website' => 'https://novaretail.test',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $ownerId)->update(['company_id' => self::$companyId]);

        $standardPlanId = DB::table('subscription_plans')->where('name', 'Standard')->value('id');
        DB::table('company_subscriptions')->insert([
            'company_id' => self::$companyId, 'plan_id' => $standardPlanId,
            'start_date' => now()->subMonths(3)->toDateString(), 'end_date' => now()->addMonths(9)->toDateString(),
            'auto_renew' => true, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        // ---- Branches ----
        $branchNames = [
            ['name' => 'Downtown Branch', 'location' => 'Downtown, Amman'],
            ['name' => 'Airport Road Branch', 'location' => 'Airport Road, Amman'],
        ];
        foreach ($branchNames as $b) {
            self::$branchIds[] = DB::table('branches')->insertGetId(array_merge($b, [
                'company_id' => self::$companyId, 'status' => 'active',
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }

        // ---- Branch Managers (real numbers) ----
        $managerPhones = ['0932556713', '0991726600'];
        $managerNames = ['Michael Reed', 'Sarah Collins'];

        foreach (self::$branchIds as $i => $branchId) {
            $managerId = DB::table('users')->insertGetId([
                'phone' => $managerPhones[$i], 'password_hash' => $pwHash, 'user_type' => 'manager',
                'status' => 'active', 'company_id' => self::$companyId, 'branch_id' => $branchId,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            DB::table('user_profiles')->insert([
                'user_id' => $managerId, 'full_name' => $managerNames[$i],
                'national_id' => 'N10001000' . ($i + 1), 'date_of_birth' => '1985-06-15',
                'gender' => $i === 0 ? 'male' : 'female', 'created_at' => now(), 'updated_at' => now(),
            ]);

            $allPermissionIds = DB::table('permissions')->pluck('id');
            foreach ($allPermissionIds as $permId) {
                DB::table('model_has_permissions')->insert([
                    'permission_id' => $permId, 'model_type' => 'App\\Models\\Identity\\User', 'model_id' => $managerId,
                ]);
            }

            self::$managerIds[$branchId] = $managerId;
            $this->command->info("Manager ({$managerNames[$i]}): {$managerPhones[$i]}");
        }

        // ---- Departments (4 per branch) ----
        $deptNames = ['IT', 'HR', 'Sales', 'Operations'];
        foreach (self::$branchIds as $branchId) {
            foreach ($deptNames as $name) {
                self::$departmentIds[$branchId][] = DB::table('departments')->insertGetId([
                    'branch_id' => $branchId, 'name' => $name, 'status' => 'active',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // ---- Supervisors: 2 real (branch 1) + 2 placeholder (branch 2) ----
        // ⚠️ الأول والتاني أرقام حقيقية بالظبط زي ما طلبت — والاتنين في نفس الفرع الأول
        $supervisorPhones = ['0988232386', '0981936633', '0900000203', '0900000204'];
        $supervisorNames = ['James Carter', 'Emily Bennett', 'David Foster', 'Olivia Martin'];
        $supervisorPermissions = ['employees.view', 'tasks.view', 'tasks.create', 'attendance.view', 'leaves.view', 'exceptions.view'];
        $supervisorPermIds = DB::table('permissions')->whereIn('name', $supervisorPermissions)->pluck('id');
        $counter = 1;

        foreach (self::$branchIds as $branchId) {
            for ($i = 0; $i < 2; $i++) {
                $phone = $supervisorPhones[$counter - 1];

                $supId = DB::table('users')->insertGetId([
                    'phone' => $phone, 'password_hash' => $pwHash, 'user_type' => 'supervisor',
                    'status' => 'active', 'branch_id' => $branchId, 'created_at' => now(), 'updated_at' => now(),
                ]);

                DB::table('user_profiles')->insert([
                    'user_id' => $supId, 'full_name' => $supervisorNames[$counter - 1],
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                foreach ($supervisorPermIds as $permId) {
                    DB::table('model_has_permissions')->insert([
                        'permission_id' => $permId, 'model_type' => 'App\\Models\\Identity\\User', 'model_id' => $supId,
                    ]);
                }

                // كل مشرف يتربط بقسمين، ونحفظ أول قسم كـ "القسم الأساسي" بتاعه للموظفين
                $depts = array_slice(self::$departmentIds[$branchId], $i * 2, 2);
                foreach ($depts as $deptId) {
                    DB::table('departments')->where('id', $deptId)->update(['supervisor_user_id' => $supId]);
                }
                self::$supervisorDepartment[$supId] = $depts[0];

                self::$supervisorIds[$branchId][] = $supId;
                $this->command->info("Supervisor ({$supervisorNames[$counter - 1]}): {$phone}");
                $counter++;
            }
        }
    }
}