<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE exception_requests MODIFY COLUMN status ENUM('pending', 'supervisor_reviewed', 'owner_reviewed', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // نعود للقيم الافتراضية عند التراجع
        DB::statement("ALTER TABLE exception_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};