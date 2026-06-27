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
Schema::create('announcements', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('created_by');
    $table->string('title');
    $table->text('content');
    $table->enum('target_type', ['all', 'branch', 'department', 'employee']);
    $table->unsignedBigInteger('target_id')->nullable();
    $table->date('start_date');
    $table->date('end_date');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('company_id')->references('id')->on('companies')->cascade();
    $table->foreign('created_by')->references('id')->on('users')->restrict();
});
    
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
