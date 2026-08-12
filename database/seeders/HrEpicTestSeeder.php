<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use App\Models\Organization\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HrEpicTestSeeder extends Seeder
{
    /**
     * Depends on BaseUserTestSeeder having been run already.
     * Seeds Workshops and Performance Evaluations only
     * (Exception Types are handled separately by ExceptionTypesSeeder to avoid duplication/conflicts).
     */
    public function run(): void
    {
        $supervisor = User::where('phone', '0799999999')->first();
        $employee = User::where('phone', '0791234567')->first();
        $company = Company::where('name', 'Nova Retail Group')->first();

        if (!$supervisor || !$employee || !$company) {
            $this->command->error('Error: run BaseUserTestSeeder first to create the users and company.');
            return;
        }

        DB::beginTransaction();

        try {
            $now = Carbon::now();
            $companyId = $company->id;
            $supervisorId = $supervisor->id;
            $employeeId = $employee->id;

            // ---------------------------------------------------------
            // Workshops
            // ---------------------------------------------------------
            $workshops = [
                [
                    'company_id' => $companyId,
                    'branch_id'  => null, // available to all branches
                    'created_by' => $supervisorId,
                    'title'      => 'Information Security Workshop',
                    'description'=> 'Covers how to protect company data from breaches.',
                    'location'   => 'Main Conference Room',
                    'start_date' => Carbon::now()->addDays(15)->setTime(10, 0),
                    'end_date'   => Carbon::now()->addDays(15)->setTime(12, 0),
                    'capacity'   => 2,
                    'status'     => 'upcoming',
                ],
                [
                    'company_id' => $companyId,
                    'branch_id'  => $employee->employeeDetail?->department?->branch_id,
                    'created_by' => $supervisorId,
                    'title'      => 'Flutter Development Workshop',
                    'description'=> 'Teaches the basics of building user interfaces.',
                    'location'   => 'Branch Training Room',
                    'start_date' => Carbon::now()->addDays(20)->setTime(9, 0),
                    'end_date'   => Carbon::now()->addDays(20)->setTime(11, 0),
                    'capacity'   => 0, // unlimited
                    'status'     => 'upcoming',
                ],
            ];

            foreach ($workshops as $workshop) {
                DB::table('workshops')->updateOrInsert(
                    ['title' => $workshop['title'], 'company_id' => $companyId],
                    array_merge($workshop, ['created_at' => $now, 'updated_at' => $now])
                );
            }

            // ---------------------------------------------------------
            // Evaluation criteria + a sample evaluation
            // ---------------------------------------------------------
            DB::table('evaluation_criteria')->updateOrInsert(
                ['name' => 'Work Performance & Productivity', 'company_id' => $companyId],
                [
                    'company_id' => $companyId,
                    'name'       => 'Work Performance & Productivity',
                    'description'=> 'Evaluates speed and quality of task completion.',
                    'weight'     => 80.00,
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $criteriaId = DB::table('evaluation_criteria')
                ->where('name', 'Work Performance & Productivity')
                ->where('company_id', $companyId)
                ->first()->id;

            DB::table('performance_evaluations')->updateOrInsert(
                ['employee_user_id' => $employeeId, 'period_start' => '2023-10-01'],
                [
                    'company_id'        => $companyId,
                    'employee_user_id'  => $employeeId,
                    'supervisor_user_id'=> $supervisorId,
                    'period_start'      => '2023-10-01',
                    'period_end'        => '2023-12-31',
                    'overall_score'     => 85.50,
                    'notes'             => 'Excellent performance last quarter with full punctuality.',
                    'status'            => 'completed',
                    'read_at'           => null, // not read yet, for testing mark-read
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]
            );
            $evaluationId = DB::table('performance_evaluations')
                ->where('employee_user_id', $employeeId)
                ->where('period_start', '2023-10-01')
                ->first()->id;

            DB::table('evaluation_scores')->updateOrInsert(
                ['evaluation_id' => $evaluationId, 'criteria_id' => $criteriaId],
                [
                    'evaluation_id' => $evaluationId,
                    'criteria_id'   => $criteriaId,
                    'score'         => 85.50,
                    'comments'      => 'Fast turnaround with high accuracy.',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]
            );

            DB::commit();
            $this->command->info('✅ Workshops and evaluation test data seeded successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding HR epics: ' . $e->getMessage());
        }
    }
}