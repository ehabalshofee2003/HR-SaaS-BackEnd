<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('users')->where('email', 'admin@hrsaas.test')->exists()) {
            return;
        }

        $id = DB::table('users')->insertGetId([
            'email' => 'admin@hrsaas.test',
            'password_hash' => Hash::make('Password123'),
            'user_type' => 'super_admin',
            'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('user_profiles')->insert([
            'user_id' => $id, 'full_name' => 'Platform Administrator',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->command->info("Super Admin: admin@hrsaas.test / Password123");
    }
}