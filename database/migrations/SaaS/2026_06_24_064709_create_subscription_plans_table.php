<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('subscription_plans', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('name');
        $table->decimal('price', 15, 4)->default(0);
        $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly']);
        $table->integer('max_branches')->nullable();
        $table->integer('max_employees')->nullable();
        $table->json('features')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
