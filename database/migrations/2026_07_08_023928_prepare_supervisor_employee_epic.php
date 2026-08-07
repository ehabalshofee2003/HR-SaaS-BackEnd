<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. إضافة نوع العقد والراتب الأساسي لجدول employee_details
        Schema::table('employee_details', function (Blueprint $table) {
            $table->enum('contract_type', ['full_time', 'part_time', 'contract'])->default('full_time')->after('job_title');
            $table->decimal('basic_salary', 15, 4)->default(0)->after('contract_type');
        });

        // 2. إنشاء جدول المستندات (لأنه غير موجود في مشروعك نهائياً)
        Schema::create('user_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->morphs('documentable'); // يضيف documentable_type و documentable_id
            $table->string('type'); // نوع المستند (مثلاً: contract, national_id)
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->restrict();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_documents');
        
        Schema::table('employee_details', function (Blueprint $table) {
            $table->dropColumn(['contract_type', 'basic_salary']);
        });
    }
};