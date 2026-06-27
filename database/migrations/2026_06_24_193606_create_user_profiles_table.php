<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            
            // الحقول الأساسية
            $table->string('full_name');
            $table->string('avatar')->nullable();
            
            // الحقول التي تسبب الخطأ (تمت إضافتها)
            $table->string('national_id')->nullable()->unique(); // رقم الهوية
            $table->date('date_of_birth')->nullable();           // تاريخ الميلاد
            
            // حقول إضافية شائعة (أضفها للحماية المستقبلية)
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->text('bio')->nullable();
            
            $table->timestamps();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};