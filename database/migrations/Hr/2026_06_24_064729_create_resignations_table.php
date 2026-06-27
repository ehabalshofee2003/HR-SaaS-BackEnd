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
Schema::create('resignations', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('employee_user_id');
    $table->unsignedBigInteger('supervisor_user_id');
    $table->text('reason');
    $table->date('notice_date');
    $table->date('last_working_date');
    $table->enum('status', ['pending', 'approved', 'rejected', 'withdrawn'])->default('pending');
    $table->unsignedBigInteger('approved_by')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->unsignedBigInteger('rejected_by')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('employee_user_id')->references('id')->on('users')->cascade();
    $table->foreign('supervisor_user_id')->references('id')->on('users')->restrict();
    $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
    $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
});

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resignations');
    }
};
