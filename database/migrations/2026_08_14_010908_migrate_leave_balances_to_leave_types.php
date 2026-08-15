<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leave_balances', 'leave_type_id')) {
            Schema::table('leave_balances', function (Blueprint $table) {
                $table->foreignId('leave_type_id')->nullable()->after('policy_id')->constrained('leave_types')->cascadeOnDelete();
            });

            DB::statement("
                UPDATE leave_balances lb
                JOIN leave_policies lp ON lp.id = lb.policy_id
                JOIN leave_types lt ON lt.code = lp.leave_type
                SET lb.leave_type_id = lt.id
            ");
        }

        // الخطوة الحاسمة: ننشئ الـ Index الجديد الأول (لسه فيه employee_user_id كأول عمود)
        // عشان يبقى فيه فهرس بديل يدعم FK بتاع employee_user_id قبل ما نمسح القديم
        $newUnique = DB::select("SHOW INDEX FROM leave_balances WHERE Key_name = 'leave_balances_emp_type_year_unique'");

        if (empty($newUnique) && Schema::hasColumn('leave_balances', 'leave_type_id')) {
            Schema::table('leave_balances', function (Blueprint $table) {
                $table->unique(['employee_user_id', 'leave_type_id', 'year'], 'leave_balances_emp_type_year_unique');
            });
        }

        if (Schema::hasColumn('leave_balances', 'policy_id')) {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'leave_balances'
                AND COLUMN_NAME = 'policy_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");

            foreach ($foreignKeys as $fk) {
                DB::statement("ALTER TABLE leave_balances DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            $indexes = DB::select("SHOW INDEX FROM leave_balances WHERE Column_name = 'policy_id'");
            $indexNames = array_unique(array_column($indexes, 'Key_name'));

            foreach ($indexNames as $indexName) {
                if ($indexName !== 'PRIMARY') {
                    DB::statement("ALTER TABLE leave_balances DROP INDEX `{$indexName}`");
                }
            }

            DB::statement("ALTER TABLE leave_balances DROP COLUMN `policy_id`");
        }
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