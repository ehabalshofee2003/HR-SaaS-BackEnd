<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // نتحقق أننا في بيئة التطوير المحلية لمنع مسح بيانات السيرفر الحقيقي بالخطأ
        if (app()->environment('local')) {
            $this->call([
                DevTestDataSeeder::class,
            ]);
        } else {
            $this->command->warn('تم إيقاف تشغيل البيانات الوهمية لأن البيئة ليست Local.');
        }
    }
}