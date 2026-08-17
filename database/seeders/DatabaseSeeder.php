<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,        // catalog of permissions (already exists in project)
            SubscriptionPlanSeeder::class,
            SuperAdminSeeder::class,
            OrganizationSeeder::class,      // owner + company + branches + departments + managers + supervisors
            StaffSeeder::class,             // leave types, exception types, employees
            OperationsSeeder::class,        // attendance, leaves, exceptions, tasks
            PayrollWorkshopSeeder::class,   // payroll, workshops, announcements, complaints, resignations, notifications
        ]);
    }
}