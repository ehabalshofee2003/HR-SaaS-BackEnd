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
Schema::create('evaluation_scores', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('evaluation_id');
    $table->unsignedBigInteger('criteria_id');
    $table->decimal('score', 5, 2)->default(0);
    $table->text('comments')->nullable();
    $table->timestamps();

    $table->foreign('evaluation_id')->references('id')->on('performance_evaluations')->cascade();
    $table->foreign('criteria_id')->references('id')->on('evaluation_criteria')->restrict();
});
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_scores');
    }
};
