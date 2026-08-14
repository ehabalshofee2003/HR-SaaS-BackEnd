<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->foreignId('leave_type_id')->nullable()->after('policy_id')->constrained('leave_types')->cascadeOnDelete();
        });

        DB::statement("
            UPDATE leave_balances lb
            JOIN leave_policies lp ON lp.id = lb.policy_id
            JOIN leave_types lt ON lt.code = lp.leave_type
            SET lb.leave_type_id = lt.id
        ");

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropForeign(['policy_id']);
            $table->dropColumn('policy_id');
            $table->unique(['employee_user_id', 'leave_type_id', 'year'], 'leave_balances_emp_type_year_unique');
        });
    }

    public function down(): void
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropUnique('leave_balances_emp_type_year_unique');
            $table->dropForeign(['leave_type_id']);
            $table->dropColumn('leave_type_id');
            $table->unsignedBigInteger('policy_id')->nullable()->after('employee_user_id');
        });
    }
};