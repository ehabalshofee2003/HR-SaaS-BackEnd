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

        Schema::create('employee_details', function (Blueprint $table) {
            $table->id();
            
            // ربط بحساب المستخدم (1 إلى 1)
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            
            // ربط بالقسم (من مجلد Organization)
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            
            // بيانات الوظيفة المطلوبة في الـ Seeder
            $table->string('job_title');
            $table->enum('employment_status', ['active', 'probation', 'terminated', 'resigned'])->default('active');
            $table->date('hire_date');
            
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_details');
    }
};