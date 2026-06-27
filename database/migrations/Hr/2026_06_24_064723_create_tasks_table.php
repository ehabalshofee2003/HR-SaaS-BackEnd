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
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('employee_user_id');
            $table->unsignedBigInteger('supervisor_user_id');
    $table->unsignedBigInteger('template_id')->nullable();
    $table->string('title');
    $table->text('description')->nullable();
    $table->enum('type', ['daily', 'ad_hoc']);
    $table->dateTime('due_date');
    $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
    $table->timestamp('completed_at')->nullable();
    $table->decimal('reward_amount', 15, 4)->default(0);
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('company_id')->references('id')->on('companies')->restrict();
    $table->foreign('employee_user_id')->references('id')->on('users')->cascade();
    $table->foreign('supervisor_user_id')->references('id')->on('users')->restrict();
    $table->foreign('template_id')->references('id')->on('task_templates')->nullOnDelete();
 });
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
