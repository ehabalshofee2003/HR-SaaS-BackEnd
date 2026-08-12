<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Identity\User;

class SupervisorTestSeeder extends Seeder
{
    /**
     * Creates a second standalone supervisor account (not tied to employee_details,
     * since supervisors never have an employee_details record in this system).
     */
    public function run(): void
    {
        $branch = DB::table('branches')->first();

        if (!$branch) {
            $this->command->error('No branches found! Run BaseUserTestSeeder first.');
            return;
        }

        $existingUser = User::where('phone', '0999999999')->first();
        if ($existingUser) {
            $this->command->info('Test supervisor account already exists.');
            return;
        }

        $supervisorId = DB::table('users')->insertGetId([
            'phone' => '0999999999',
            'password_hash' => Hash::make('12345678'),
            'user_type' => 'supervisor',
            'status' => 'active',
            'branch_id' => $branch->id, // supervisors link to a branch directly, not via employee_details
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('user_profiles')->insert([
            'user_id' => $supervisorId,
            'full_name' => 'Extra Test Supervisor',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->command->info('✅ Test supervisor account created successfully!');
        $this->command->warn('Phone: 0999999999');
        $this->command->warn('Password: 12345678');
    }
}