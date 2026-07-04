<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_details', function (Blueprint $table) {
            // نستخدم نفس النمط الحديث مع تمرير اسم الجدول 'users' لضمان الدقة
            // nullOnDelete لأنه إذا تم حذف المشرف لا يجب أن نحذف بيانات الموظف، بل نُفرغ الحقل
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_details', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn('supervisor_id');
        });
    }
};