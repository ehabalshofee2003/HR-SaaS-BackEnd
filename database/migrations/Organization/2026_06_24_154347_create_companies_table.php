<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // نستخدمها احتياطياً في حال كان هناك جدول آخر يعتمد على هذا الجدول 
        // ومجلده يأتي أبجدياً قبل مجلد Organization
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete(); // صاحب الشركة
            $table->string('name');
            $table->string('status')->default('active'); // active, suspended, etc.
            $table->string('logo')->nullable();
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};