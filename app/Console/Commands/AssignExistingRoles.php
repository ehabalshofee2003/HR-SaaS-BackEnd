<?php

namespace App\Console\Commands;

use App\Models\Identity\User;
use Illuminate\Console\Command;

class AssignExistingRoles extends Command
{
    protected $signature = 'roles:backfill';
    protected $description = 'يعيّن الأدوار (Spatie roles) للمستخدمين الموجودين مسبقًا حسب user_type';

    public function handle(): int
    {
        $roleMap = [
            'manager' => 'manager',
            'supervisor' => 'supervisor',
            'owner' => 'owner',
            'super_admin' => 'super_admin',
        ];

        foreach ($roleMap as $userType => $roleName) {
            $users = User::where('user_type', $userType)->get();

            foreach ($users as $user) {
                if (!$user->hasRole($roleName)) {
                    $user->assignRole($roleName);
                    $this->info("تم تعيين role '{$roleName}' لليوزر #{$user->id}");
                }
            }
        }

        $this->info('انتهى تعيين الأدوار بنجاح.');
        return self::SUCCESS;
    }
}