<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExceptionTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Justified Absence', 'slug' => 'justified_absence'],
            ['name' => 'Deduction Reconsideration', 'slug' => 'deduction_reconsideration'],
            ['name' => 'Salary Addition', 'slug' => 'salary_addition'],
            ['name' => 'Exceptional Leave', 'slug' => 'exceptional_leave'],
        ];

        foreach ($types as $type) {
            DB::table('exception_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->warn('✅ Exception types seeded successfully (English).');
    }
}