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
Schema::create('payroll_adjustments', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('payroll_record_id');
    $table->unsignedBigInteger('created_by');
    $table->enum('adjustment_type', ['correction', 'bonus', 'deduction']);
    $table->decimal('amount', 15, 4)->default(0);
    $table->text('reason')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('payroll_record_id')->references('id')->on('payroll_records')->cascade();
    $table->foreign('created_by')->references('id')->on('users')->restrict();
});

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
    }
};
