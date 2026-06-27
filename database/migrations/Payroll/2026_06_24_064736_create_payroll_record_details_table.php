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
Schema::create('payroll_record_details', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('record_id');
    $table->string('name');
    $table->enum('component_type', ['base_salary', 'bonus', 'deduction', 'allowance', 'overtime']);
    $table->decimal('amount', 15, 4)->default(0);
    $table->timestamps();

    $table->foreign('record_id')->references('id')->on('payroll_records')->cascade();
});

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_record_details');
    }
};
