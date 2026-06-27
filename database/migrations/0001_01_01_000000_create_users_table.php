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
// 2026_06_20_000003_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('email')->nullable()->unique();
    $table->string('phone')->unique();
    $table->string('password_hash');
    $table->enum('user_type', ['super_admin', 'owner', 'manager', 'supervisor', 'employee']);
    $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
    $table->boolean('two_factor_enabled')->default(false);
    $table->timestamp('last_login_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
