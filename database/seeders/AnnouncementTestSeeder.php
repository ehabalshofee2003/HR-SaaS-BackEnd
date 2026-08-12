<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use App\Models\Support\Announcement;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AnnouncementTestSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('phone', '0791234567')->first();
        if (!$user) {
            $this->command->error('Test user not found!');
            return;
        }

        $companyId = $user->getCurrentCompanyId();
        $supervisor = User::where('phone', '0799999999')->first();

        Announcement::firstOrCreate(
            ['company_id' => $companyId, 'target_type' => 'all', 'title' => 'Important: Bonus Distribution'],
            [
                'created_by' => $supervisor ? $supervisor->id : $user->id,
                'content' => 'End-of-month bonuses have been approved for all employees at the main branch. Please review your payslip.',
                'target_id' => null,
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->addDays(7)->toDateString(),
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Announcement test data created successfully!');
    }
}