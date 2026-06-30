<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::create('exception_requests', function (Blueprint $table) {
            $table->id();
            
            // ربط بالشركة والموظف
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employee_details')->cascadeOnDelete();            
            // نوع الاستثناء (مثال: overtime, late_arrival, early_departure, holiday_work)
            // نفترض وجود جدول exception_types كما هو معتاد في أنظمة SaaS
            $table->foreignId('exception_type_id')->constrained()->restrictOnDelete();
            
            // بيانات الاستثناء نفسه
            $table->date('request_date'); // تاريخ حدوث الاستثناء
            $table->time('start_time')->nullable(); // وقت البداية (مهم للـ Overtime)
            $table->time('end_time')->nullable();   // وقت النهاية
            $table->integer('duration_minutes')->default(0); // المدة بالدقائق (لتسهيل حساب الرواتب لاحقاً)
            
            $table->text('reason'); // سبب الطلب
            $table->string('attachment')->nullable(); // مرفق (مثل تقرير طبي لتأخير)
            
            // مسار الاعتماد (Approval Workflow)
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete(); // من وافق/رفض
            $table->timestamp('approved_at')->nullable(); // تاريخ الاعتماد
            $table->text('rejection_reason')->nullable(); // سبب الرفض
            
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        Schema::dropIfExists('exception_requests');
    }
};