<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تحويل الحقل إلى VARCHAR(50) ليدعم pending_manager وأي حالات مستقبلية
        DB::statement("ALTER TABLE exception_requests MODIFY status VARCHAR(50) NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // العودة إلى ENUM في حال إلغاء التهجير
        DB::statement("ALTER TABLE exception_requests MODIFY status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending'");
    }
};