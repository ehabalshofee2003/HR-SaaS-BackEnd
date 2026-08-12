<?php

namespace Database\Seeders;

use App\Models\Identity\User;
use App\Models\Hr\LeaveRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveRequestTestSeeder extends Seeder
{
    public function run(): void
    {
        $employee = User::where('phone', '0791234567')->first();
        $manager = User::where('phone', '0798888888')->first();

        if (!$employee) {
            $this->command->error('Test employee not found. Run BaseUserTestSeeder first.');
            return;
        }

        $companyId = $employee->getCurrentCompanyId();

        $leaveTypes = DB::table('leave_types')
            ->where('company_id', $companyId)
            ->pluck('id', 'code');

        if ($leaveTypes->isEmpty()) {
            $this->command->error('No leave types found. Run LeaveTypesTestSeeder first.');
            return;
        }

        $requests = [
            [
                'leave_type_id' => $leaveTypes['annual'] ?? null,
                'start_date' => Carbon::now()->addDays(10)->toDateString(),
                'end_date' => Carbon::now()->addDays(12)->toDateString(),
                'reason' => 'Family trip planned for next month.',
                'status' => 'pending',
                'approver_id' => null,
                'approved_at' => null,
                'rejection_reason' => null,
            ],
            [
                'leave_type_id' => $leaveTypes['sick'] ?? null,
                'start_date' => Carbon::now()->subDays(5)->toDateString(),
                'end_date' => Carbon::now()->subDays(4)->toDateString(),
                'reason' => 'Flu symptoms, doctor recommended rest.',
                'status' => 'approved',
                'approver_id' => $manager?->id,
                'approved_at' => Carbon::now()->subDays(5),
                'rejection_reason' => null,
            ],
            [
                'leave_type_id' => $leaveTypes['emergency'] ?? null,
                'start_date' => Carbon::now()->subDays(15)->toDateString(),
                'end_date' => Carbon::now()->subDays(15)->toDateString(),
                'reason' => 'Urgent family matter.',
                'status' => 'rejected',
                'approver_id' => $manager?->id,
                'approved_at' => Carbon::now()->subDays(15),
                'rejection_reason' => 'Insufficient notice given, please plan ahead next time.',
            ],
            [
                'leave_type_id' => $leaveTypes['annual'] ?? null,
                'start_date' => Carbon::now()->subDays(30)->toDateString(),
                'end_date' => Carbon::now()->subDays(28)->toDateString(),
                'reason' => 'Personal errands.',
                'status' => 'cancelled',
                'approver_id' => null,
                'approved_at' => null,
                'rejection_reason' => null,
            ],
        ];

        foreach ($requests as $req) {
            if (!$req['leave_type_id']) {
                continue;
            }

            LeaveRequest::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'start_date' => $req['start_date'],
                    'end_date' => $req['end_date'],
                ],
                [
                    'company_id' => $companyId,
                    'leave_type_id' => $req['leave_type_id'],
                    'reason' => $req['reason'],
                    'status' => $req['status'],
                    'approver_id' => $req['approver_id'],
                    'approved_at' => $req['approved_at'],
                    'rejection_reason' => $req['rejection_reason'],
                ]
            );
        }

        $this->command->info('✅ Test leave requests seeded (1 pending, 1 approved, 1 rejected, 1 cancelled).');
    }
}