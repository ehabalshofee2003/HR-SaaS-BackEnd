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
Schema::create('employee_salaries', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('employee_user_id');
    $table->unsignedBigInteger('template_id');
    $table->boolean('is_active')->default(true);
    $table->date('effective_from');
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('employee_user_id')->references('id')->on('users')->cascade();
    $table->foreign('template_id')->references('id')->on('salary_templates')->restrict();
});

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
