<?php

namespace App\Console\Commands;

use App\Models\Identity\User;
use App\Models\Identity\UserProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestEmployee extends Command
{
    protected $signature = 'employee:create {phone} {name=Test Employee}';

    protected $description = 'Create a quick test Employee user with a given phone number (for OTP testing)';

    public function handle(): int
    {
        $phone = $this->argument('phone');
        $name = $this->argument('name');

        if (User::where('phone', $phone)->exists()) {
            $this->error("رقم {$phone} موجود بالفعل في النظام.");
            return self::FAILURE;
        }

        $user = User::create([
            'phone' => $phone,
            'password_hash' => Hash::make('123456'),
            'user_type' => 'employee',
            'status' => 'active',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'full_name' => $name,
        ]);

        $this->info('=====================================================');
        $this->info("تم إنشاء Employee بنجاح!");
        $this->info("Phone: {$phone}");
        $this->info("Name: {$name}");
        $this->info("ID: {$user->id}");
        $this->info('=====================================================');

        return self::SUCCESS;
    }
}