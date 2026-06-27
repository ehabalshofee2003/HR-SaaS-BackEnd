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
Schema::create('template_components', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('template_id');
    $table->string('name');
    $table->enum('component_type', ['base_salary', 'bonus', 'deduction', 'allowance', 'overtime']);
    $table->decimal('amount', 15, 4)->default(0);
    $table->boolean('is_percentage')->default(false);
    $table->string('calculation_base')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('template_id')->references('id')->on('salary_templates')->cascade();
});

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_components');
    }
};
