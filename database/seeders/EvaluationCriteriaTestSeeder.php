<?php

namespace Database\Seeders;

use App\Models\Organization\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvaluationCriteriaTestSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Nova Retail Group')->first();

        if (!$company) {
            $this->command->error('Test company not found. Run BaseUserTestSeeder first.');
            return;
        }

        if (DB::table('evaluation_criteria')->where('company_id', $company->id)->exists()) {
            $this->command->info('Evaluation criteria already exist for this company.');
            return;
        }

        $criteria = [
            ['name' => 'Quality', 'description' => 'Quality of completed work', 'weight' => 20],
            ['name' => 'Productivity', 'description' => 'Amount of work completed within the deadline', 'weight' => 20],
            ['name' => 'Attendance', 'description' => 'Punctuality and adherence to work hours', 'weight' => 20],
            ['name' => 'Collaboration', 'description' => 'Teamwork with colleagues', 'weight' => 20],
            ['name' => 'Discipline', 'description' => 'Following instructions and workplace conduct', 'weight' => 20],
        ];

        foreach ($criteria as $item) {
            DB::table('evaluation_criteria')->insert([
                'company_id' => $company->id,
                'name' => $item['name'],
                'description' => $item['description'],
                'weight' => $item['weight'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->warn('✅ 5 evaluation criteria seeded for the test company.');
    }
}