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

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // مثال: إجازة سنوية
            $table->string('code')->unique(); // مثال: ANNUAL
            $table->text('description')->nullable();
            $table->boolean('is_paid')->default(true); // مدفوعة الراتب؟
            $table->integer('max_days_per_year')->default(0); // الحد الأقصى للأيام
            $table->boolean('requires_attachment')->default(false); // تتطلب مرفق؟
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};