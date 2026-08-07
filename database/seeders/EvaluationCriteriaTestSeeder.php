<?php

namespace Database\Seeders;

use App\Models\Organization\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvaluationCriteriaTestSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Badran Poultry Test')->first();

        if (!$company) {
            $this->command->error('الشركة التجريبية غير موجودة. شغّل BaseUserTestSeeder أولاً.');
            return;
        }

        if (DB::table('evaluation_criteria')->where('company_id', $company->id)->exists()) {
            $this->command->info('المعايير موجودة مسبقاً لهذه الشركة.');
            return;
        }

        $criteria = [
            ['name' => 'الجودة', 'description' => 'جودة العمل المُنجز', 'weight' => 20],
            ['name' => 'الإنتاجية', 'description' => 'كمية العمل المُنجز بالوقت المحدد', 'weight' => 20],
            ['name' => 'الحضور', 'description' => 'الالتزام بمواعيد الدوام', 'weight' => 20],
            ['name' => 'التعاون', 'description' => 'العمل الجماعي مع الزملاء', 'weight' => 20],
            ['name' => 'الالتزام', 'description' => 'الانضباط والالتزام بالتعليمات', 'weight' => 20],
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

        $this->command->warn('تم زرع 5 معايير تقييم للشركة التجريبية بنجاح.');
    }
}