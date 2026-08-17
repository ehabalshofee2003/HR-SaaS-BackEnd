<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Basic', 'price' => 50, 'billing_cycle' => 'monthly', 'max_branches' => 1, 'max_employees' => 10, 'features' => json_encode(['attendance', 'tasks'])],
            ['name' => 'Standard', 'price' => 125, 'billing_cycle' => 'monthly', 'max_branches' => 5, 'max_employees' => 50, 'features' => json_encode(['attendance', 'tasks', 'payroll', 'reports'])],
            ['name' => 'Premium', 'price' => 250, 'billing_cycle' => 'monthly', 'max_branches' => null, 'max_employees' => null, 'features' => json_encode(['attendance', 'tasks', 'payroll', 'reports', 'workshops', 'complaints'])],
        ];

        foreach ($plans as $p) {
            DB::table('subscription_plans')->insert(array_merge($p, [
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]));
        }
    }
}