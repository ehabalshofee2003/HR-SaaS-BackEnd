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
Schema::create('notifications', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('user_id');
    $table->string('title');
    $table->text('body');
    $table->enum('type', ['system', 'attendance', 'task', 'leave', 'payroll', 'announcement']);
    $table->boolean('is_read')->default(false);
    $table->json('data')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('company_id')->references('id')->on('companies')->cascade();
    $table->foreign('user_id')->references('id')->on('users')->cascade();
    
    $table->index(['user_id', 'is_read']); // لتسريع جلب الإشعارات غير المقروءة
});

        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
