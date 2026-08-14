<?php

namespace App\Console\Commands;

use App\Models\Identity\User;
use App\Models\Identity\UserProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestOwner extends Command
{
    protected $signature = 'owner:create {phone} {name=Test Owner}';

    protected $description = 'Create a quick test Owner user with a given phone number (for OTP testing)';

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
            'user_type' => 'owner',
            'status' => 'active',
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'full_name' => $name,
        ]);

        $this->info('=====================================================');
        $this->info("تم إنشاء Owner بنجاح!");
        $this->info("Phone: {$phone}");
        $this->info("Name: {$name}");
        $this->info("ID: {$user->id}");
        $this->info('=====================================================');

        return self::SUCCESS;
    }
}