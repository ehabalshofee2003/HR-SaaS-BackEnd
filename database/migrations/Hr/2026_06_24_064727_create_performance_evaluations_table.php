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
Schema::create('performance_evaluations', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('employee_user_id');
    $table->unsignedBigInteger('supervisor_user_id');
    $table->date('period_start');
    $table->date('period_end');
    $table->decimal('overall_score', 5, 2)->default(0);
    $table->text('notes')->nullable();
    $table->enum('status', ['draft', 'completed', 'reviewed'])->default('draft');
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('company_id')->references('id')->on('companies')->restrict();
    $table->foreign('employee_user_id')->references('id')->on('users')->cascade();
    $table->foreign('supervisor_user_id')->references('id')->on('users')->restrict();
});

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_evaluations');
    }
};
