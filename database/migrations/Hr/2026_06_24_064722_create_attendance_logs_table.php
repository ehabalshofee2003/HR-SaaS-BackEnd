<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

Schema::create('attendance_logs', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('employee_user_id');
    $table->unsignedBigInteger('branch_id');
    $table->unsignedBigInteger('qr_code_id')->nullable();
    $table->dateTime('check_in');
    $table->dateTime('check_out')->nullable();
    $table->decimal('work_hours', 5, 2)->default(0);
    $table->enum('type', ['qr', 'manual']);
    $table->enum('status', ['present', 'late', 'absent', 'early_leave'])->default('present');
    $table->text('notes')->nullable();
    $table->string('location', 500)->nullable();
    $table->unsignedBigInteger('reviewed_by_supervisor')->nullable();
    $table->timestamp('reviewed_at_supervisor')->nullable();
    $table->unsignedBigInteger('reviewed_by_manager')->nullable();
    $table->timestamp('reviewed_at_manager')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('company_id')->references('id')->on('companies')->restrict();
    $table->foreign('employee_user_id')->references('id')->on('users')->cascade();
    $table->foreign('branch_id')->references('id')->on('branches')->restrict();
    $table->foreign('qr_code_id')->references('id')->on('qr_codes')->nullOnDelete();
    $table->foreign('reviewed_by_supervisor')->references('id')->on('users')->nullOnDelete();
    $table->foreign('reviewed_by_manager')->references('id')->on('users')->nullOnDelete();

    // فهارس لتحسين سرعة البحث عن سجلات الحضور
    $table->index(['employee_user_id', 'check_in']); 
});
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
