<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('exception_requests', function (Blueprint $table) {
        $table->string('manager_note', 500)->nullable()->after('rejection_reason');
    });
}

public function down(): void
{
    Schema::table('exception_requests', function (Blueprint $table) {
        $table->dropColumn('manager_note');
    });
}
};
