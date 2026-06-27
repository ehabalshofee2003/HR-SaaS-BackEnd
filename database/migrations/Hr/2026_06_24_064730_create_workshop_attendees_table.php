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
Schema::create('workshop_attendees', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('workshop_id');
    $table->unsignedBigInteger('employee_user_id');
    $table->enum('status', ['registered', 'attended', 'absent', 'cancelled'])->default('registered');
    $table->timestamp('registered_at')->useCurrent();
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('workshop_id')->references('id')->on('workshops')->cascade();
    $table->foreign('employee_user_id')->references('id')->on('users')->cascade();
    
    // ممنوع الموظف يسجل نفسه مرتين بنفس الورشة
    $table->unique(['workshop_id', 'employee_user_id']); 
});

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_attendees');
    }
};
