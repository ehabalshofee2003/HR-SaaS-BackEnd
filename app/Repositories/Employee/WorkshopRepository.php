<?php

namespace App\Repositories\Employee;

use Illuminate\Support\Facades\DB;

class WorkshopRepository
{
    public function listForEmployee(int $employeeUserId, int $branchId): array
    {
        return DB::table('workshops as w')
            ->join('users as u', 'u.id', '=', 'w.created_by')
            ->join('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->where('w.branch_id', $branchId)
            ->whereIn('w.audience', ['employee', 'all'])
            ->whereIn('w.status', ['upcoming', 'ongoing'])
            ->whereNull('w.deleted_at')
            ->select([
                'w.id', 'w.title', 'w.start_date', 'w.end_date', 'w.location',
                'w.capacity', 'p.full_name as responsible_name',
                DB::raw('(SELECT COUNT(*) FROM workshop_attendees wa WHERE wa.workshop_id = w.id AND wa.status = "registered") as registered_count'),
            ])
            ->orderBy('w.start_date')
            ->get()
            ->all();
    }

    public function find(int $id): ?object
    {
        return DB::table('workshops')->where('id', $id)->whereNull('deleted_at')->first();
    }

    public function isRegistered(int $workshopId, int $employeeUserId): bool
    {
        return DB::table('workshop_attendees')
            ->where('workshop_id', $workshopId)
            ->where('employee_user_id', $employeeUserId)
            ->where('status', 'registered')
            ->whereNull('deleted_at')
            ->exists();
    }

    public function registeredCount(int $workshopId): int
    {
        return DB::table('workshop_attendees')
            ->where('workshop_id', $workshopId)
            ->where('status', 'registered')
            ->whereNull('deleted_at')
            ->count();
    }

    public function register(int $workshopId, int $employeeUserId): void
    {
        DB::table('workshop_attendees')->insert([
            'workshop_id' => $workshopId,
            'employee_user_id' => $employeeUserId,
            'status' => 'registered',
            'registered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function cancel(int $workshopId, int $employeeUserId): void
    {
        DB::table('workshop_attendees')
            ->where('workshop_id', $workshopId)
            ->where('employee_user_id', $employeeUserId)
            ->update(['status' => 'cancelled', 'updated_at' => now()]);
    }
}