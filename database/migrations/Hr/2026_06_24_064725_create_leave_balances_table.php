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

Schema::create('leave_balances', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('employee_user_id');
    $table->unsignedBigInteger('policy_id');
    $table->integer('year');
    $table->decimal('remaining_days', 5, 2)->default(0);
    $table->timestamps();

    $table->foreign('employee_user_id')->references('id')->on('users')->cascade();
    $table->foreign('policy_id')->references('id')->on('leave_policies')->cascade();
    $table->unique(['employee_user_id', 'policy_id', 'year']); // ممنوع تكرار رصيد لنفس الموظف لنفس السياسة بنفس السنة
});
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
