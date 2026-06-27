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
Schema::create('support_tickets', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('company_id');
    $table->unsignedBigInteger('created_by');
    $table->string('title');
    $table->text('description');
    $table->enum('category', ['technical', 'billing', 'feature_request', 'bug', 'other']);
    $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
    $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
    $table->unsignedBigInteger('assigned_to')->nullable();
    $table->text('resolution_notes')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->timestamp('closed_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('company_id')->references('id')->on('companies')->cascade();
    $table->foreign('created_by')->references('id')->on('users')->cascade();
    $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
});

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
