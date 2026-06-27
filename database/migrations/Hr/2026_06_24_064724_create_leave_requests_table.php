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

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            
            // تم توحيدها مع الكود
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete(); // كان employee_user_id
            
            // تم تحويل الـ enum إلى علاقة مع جدول leave_types (معيار SaaS)
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete(); 
            
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            
            // تم إضافة حقل المرفقات الذي كان مفقوداً
            $table->string('attachment')->nullable(); 
            
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            
            // تم توحيد من وافق/رفض في عمود واحد بدل ثلاثة أعمدة قديمة
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete(); 
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};