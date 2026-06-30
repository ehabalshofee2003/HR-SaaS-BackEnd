<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HrEpicTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * هذا السيدر يعتمد على أن BaseUserTestSeeder قد تم تشغيله مسبقاً
     * (الشركة رقم 1، الفرع رقم 1، المشرف رقم 1، الموظف رقم 2)
     */
    public function run(): void
    {
        // نستخدم Transaction لضمان نظافة البيانات في حال حدوث أي خطأ
        DB::beginTransaction();

        try {
            $now = Carbon::now();
            $companyId = 1;
            $supervisorId = 1; // user_id
            $employeeId = 2;  // user_id

            // ---------------------------------------------------------
            // 1. بيانات Epic: Exception Requests (أنواع الاستثناءات فقط)
            // ---------------------------------------------------------
            // ---------------------------------------------------------
            $exceptionTypes = [
                ['name' => 'تأخير عن الدوام', 'slug' => 'late_arrival', 'is_active' => true],
                ['name' => 'خروج مبكر', 'slug' => 'early_departure', 'is_active' => true],
                ['name' => 'عمل إضافي', 'slug' => 'overtime', 'is_active' => true],
                ['name' => 'عمل في عطلة', 'slug' => 'holiday_work', 'is_active' => true],
            ];

            foreach ($exceptionTypes as $type) {
                DB::table('exception_types')->updateOrInsert(
                    ['slug' => $type['slug']], // تمت إزالة company_id من الشرط
                    array_merge($type, ['created_at' => $now, 'updated_at' => $now]) // تمت إزالته من البيانات
                );
            }

            // ---------------------------------------------------------
            // 2. بيانات Epic: Workshops (ورش العمل)
            // ---------------------------------------------------------
            $workshops = [
                [
                    'company_id' => $companyId,
                    'branch_id'  => null, // لكل الفروع
                    'created_by' => $supervisorId,
                    'title'      => 'ورشة أمن المعلومات',
                    'description'=> 'تتحدث عن كيفية حماية بيانات الشركة من الاختراق.',
                    'location'   => 'قاعة الاجتماعات الرئيسية',
                    'start_date' => Carbon::now()->addDays(15)->setTime(10, 0),
                    'end_date'   => Carbon::now()->addDays(15)->setTime(12, 0),
                    'capacity'   => 2,
                    'status'     => 'upcoming',
                ],
                [
                    'company_id' => $companyId,
                    'branch_id'  => 1, // للفرع رقم 1 فقط
                    'created_by' => $supervisorId,
                    'title'      => 'ورشة تطوير Flutter',
                    'description'=> 'تعليم أساسيات بناء واجهات المستخدم.',
                    'location'   => 'قاعة التدريب الفرعية',
                    'start_date' => Carbon::now()->addDays(20)->setTime(9, 0),
                    'end_date'   => Carbon::now()->addDays(20)->setTime(11, 0),
                    'capacity'   => 0, // غير محدودة
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
            // 3. بيانات Epic: Evaluations (التقييمات)
            // ---------------------------------------------------------
            
            // أولاً: معايير التقييم
            DB::table('evaluation_criteria')->updateOrInsert(
                ['name' => 'أداء العمل والإنتاجية', 'company_id' => $companyId],
                [
                    'company_id' => $companyId,
                    'name'       => 'أداء العمل والإنتاجية',
                    'description'=> 'تقييم سرعة وجودة إنجاز المهام الموكلة.',
                    'weight'     => 80.00,
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $criteriaId = DB::table('evaluation_criteria')->where('name', 'أداء العمل والإنتاجية')->first()->id;

            // ثانياً: التقييم نفسه
            DB::table('performance_evaluations')->updateOrInsert(
                ['employee_user_id' => $employeeId, 'period_start' => '2023-10-01'],
                [
                    'company_id'        => $companyId,
                    'employee_user_id'  => $employeeId,
                    'supervisor_user_id'=> $supervisorId,
                    'period_start'      => '2023-10-01',
                    'period_end'        => '2023-12-31',
                    'overall_score'     => 85.50,
                    'notes'             => 'أداء ممتاز خلال الربع الأخير مع التزام تام بالمواعيد.',
                    'status'            => 'completed',
                    'read_at'           => null, // لم يقرأها الموظف بعد لاختبار راوت mark-read
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]
            );
            $evaluationId = DB::table('performance_evaluations')->where('employee_user_id', $employeeId)->first()->id;

            // ثالثاً: درجة التقييم مرتبطة بالمعيار
            DB::table('evaluation_scores')->updateOrInsert(
                ['evaluation_id' => $evaluationId, 'criteria_id' => $criteriaId],
                [
                    'evaluation_id' => $evaluationId,
                    'criteria_id'   => $criteriaId,
                    'score'         => 85.50,
                    'comments'      => 'يتميز بسرعة الانجاز والدقة العالية.',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]
            );

            DB::commit();
            $this->command->info('HR Epics Test Data seeded successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding HR Epics: ' . $e->getMessage());
        }
    }
}