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
Schema::create('payroll_records', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('employee_user_id');
    $table->unsignedBigInteger('period_id');
    $table->decimal('gross_salary', 15, 4)->default(0);
    $table->decimal('total_deductions', 15, 4)->default(0);
    $table->decimal('total_bonuses', 15, 4)->default(0);
    $table->decimal('net_salary', 15, 4)->default(0);
    $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
    $table->unsignedBigInteger('approved_by')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->timestamp('locked_at')->nullable(); // لتحقيق الـ Concurrency Control
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('employee_user_id')->references('id')->on('users')->cascade();
    $table->foreign('period_id')->references('id')->on('payroll_periods')->cascade();
    $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

    $table->unique(['employee_user_id', 'period_id']); // ممنوع حساب راتب الموظف مرتين في نفس الفترة
});
    
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};
