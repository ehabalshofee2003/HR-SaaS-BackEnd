<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('leave_balances', function (Blueprint $table) {
        $table->unsignedInteger('remaining_days')->default(0)->change();
    });
}

public function down(): void
{
    Schema::table('leave_balances', function (Blueprint $table) {
        $table->decimal('remaining_days', 5, 2)->default(0)->change();
    });
}
};
