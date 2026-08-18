<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $newPermissions = [
            'branches.view', 'branches.create', 'branches.update', 'branches.delete',
            'branches.suspend', 'branches.activate',
            'managers.view', 'managers.create', 'managers.update', 'managers.delete',
            'exceptions.approve',
        ];

        foreach ($newPermissions as $name) {
            if (!DB::table('permissions')->where('name', $name)->exists()) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};