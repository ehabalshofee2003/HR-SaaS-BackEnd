<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Identity\User;
use App\Models\Organization\Company;

class TaskTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Depends on BaseUserTestSeeder (employee 0791234567 and supervisor 0799999999)
     */
    public function run(): void
    {
        $employee = User::where('phone', '0791234567')->first();
        $supervisor = User::where('phone', '0799999999')->first();
        $company = Company::first();

        if (!$employee || !$supervisor || !$company || !$employee->employeeDetail) {
            $this->command->error('Error: run BaseUserTestSeeder first to create the users and company.');
            return;
        }

        $companyId = $company->id;
        $supervisorId = $supervisor->id;

        $now = Carbon::now();

        $tasks = [
            [
                'company_id'        => $companyId,
                'employee_user_id'  => $employee->id,
                'supervisor_user_id'=> $supervisorId,
                'title'             => 'Review daily sales reports',
                'description'       => 'Review the morning shift sales reports and confirm they match the accounting system.',
                'type'              => 'daily',
                'status'            => 'pending',
                'priority'          => 'medium',
                'due_date'          => $now->copy()->addDay()->setTime(16, 0),
                'completed_at'      => null,
                'reward_amount'     => 0,
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            [
                'company_id'        => $companyId,
                'employee_user_id'  => $employee->id,
                'supervisor_user_id'=> $supervisorId,
                'title'             => 'Prepare presentation for management meeting',
                'description'       => 'Prepare slides covering the HR department achievements for the current quarter and present them to the manager.',
                'type'              => 'ad_hoc',
                'status'            => 'in_progress',
                'priority'          => 'high',
                'due_date'          => $now->copy()->addDays(3)->setTime(12, 0),
                'completed_at'      => null,
                'reward_amount'     => 5000.00,
                'created_at'        => $now->copy()->subDay(),
                'updated_at'        => $now,
            ],
            [
                'company_id'        => $companyId,
                'employee_user_id'  => $employee->id,
                'supervisor_user_id'=> $supervisorId,
                'title'             => 'Log workshop attendance manually',
                'description'       => 'Manually log attendance for employees who attended the cybersecurity workshop, due to a fingerprint device malfunction.',
                'type'              => 'daily',
                'status'            => 'completed',
                'priority'          => 'low',
                'due_date'          => $now->copy()->subDay()->setTime(18, 0),
                'completed_at'      => $now->copy()->subDay()->setTime(17, 30),
                'reward_amount'     => 0,
                'created_at'        => $now->copy()->subDays(2),
                'updated_at'        => $now->copy()->subDay(),
            ],
            [
                'company_id'        => $companyId,
                'employee_user_id'  => $employee->id,
                'supervisor_user_id'=> $supervisorId,
                'title'             => 'Fix bonus calculation error',
                'description'       => 'There is a bug in the bonus calculation for employees who worked holidays — review and fix the logic immediately.',
                'type'              => 'ad_hoc',
                'status'            => 'pending',
                'priority'          => 'high',
                'due_date'          => $now->copy()->subDays(2)->setTime(10, 0),
                'completed_at'      => null,
                'reward_amount'     => 10000.00,
                'created_at'        => $now->copy()->subDays(5),
                'updated_at'        => $now->copy()->subDays(5),
            ],
        ];

        DB::table('tasks')->insert($tasks);

        $this->command->info('✅ 4 test tasks seeded successfully (including one overdue task for testing).');
    }
}