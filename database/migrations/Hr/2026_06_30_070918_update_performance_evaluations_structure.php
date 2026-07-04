<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. إضافة الحقل الجديد أولاً
        Schema::table('performance_evaluations', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable();
        });

        // 2. تعديل الـ Enum
        DB::statement("ALTER TABLE performance_evaluations MODIFY COLUMN status ENUM('draft', 'submitted', 'reviewed', 'completed') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // 1. إرجاع الـ Enum
        DB::statement("ALTER TABLE performance_evaluations MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");

        // 2. حذف الحقل
        Schema::table('performance_evaluations', function (Blueprint $table) {
            $table->dropColumn('submitted_at');
        });
    }
};