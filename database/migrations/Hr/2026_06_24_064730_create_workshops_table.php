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
Schema::create('workshops', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('branch_id')->nullable();
    $table->unsignedBigInteger('created_by');
    $table->string('title');
    $table->text('description')->nullable();
    $table->string('location', 500)->nullable();
    $table->dateTime('start_date');
    $table->dateTime('end_date');
    $table->integer('capacity')->default(0);
    $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming');
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('company_id')->references('id')->on('companies')->restrict();
    $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
    $table->foreign('created_by')->references('id')->on('users')->restrict();
});

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshops');
    }
};
