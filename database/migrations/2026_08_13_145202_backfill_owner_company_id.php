<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE users u
            INNER JOIN companies c ON c.owner_user_id = u.id
            SET u.company_id = c.id
            WHERE u.user_type = 'owner'
        ");
    }

    public function down(): void
    {
        //
    }
};