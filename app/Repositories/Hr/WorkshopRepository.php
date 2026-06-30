<?php

namespace App\Repositories\Hr;

use App\Models\Hr\Workshop;
use App\Models\Hr\WorkshopAttendee;
use Illuminate\Support\Facades\DB;

class WorkshopRepository
{
    public function getAvailableWorkshops(int $companyId, ?int $branchId)
    {
        return Workshop::where('company_id', $companyId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')
                  ->orWhere('branch_id', $branchId);
            })
            ->latest('start_date')
            ->paginate(15);
    }

    public function findWorkshop(int $id, int $companyId, ?int $branchId): ?Workshop
    {
        return Workshop::where('id', $id)
            ->where('company_id', $companyId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')
                  ->orWhere('branch_id', $branchId);
            })
            ->first();
    }

    public function getRegisteredWorkshopIds(int $userId): array
    {
        return WorkshopAttendee::where('employee_user_id', $userId)
            ->where('status', 'registered')
            ->pluck('workshop_id')
            ->toArray();
    }

    public function getMyWorkshops(int $userId)
    {
        return WorkshopAttendee::where('employee_user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->latest('registered_at')
            ->paginate(15);
    }

    public function getMyWorkshopById(int $workshopId, int $userId): ?WorkshopAttendee
    {
        return WorkshopAttendee::where('workshop_id', $workshopId)
            ->where('employee_user_id', $userId)
            ->where('status', '!=', 'cancelled')
            ->first();
    }

    public function getRegisteredCount(int $workshopId): int
    {
        return WorkshopAttendee::where('workshop_id', $workshopId)
            ->where('status', 'registered')
            ->count();
    }

    public function findAttendee(int $workshopId, int $userId): ?WorkshopAttendee
    {
        return WorkshopAttendee::where('workshop_id', $workshopId)
            ->where('employee_user_id', $userId)
            ->where('status', 'registered')
            ->first();
    }

    public function register(int $workshopId, int $userId): WorkshopAttendee
    {
        return WorkshopAttendee::create([
            'workshop_id'      => $workshopId,
            'employee_user_id' => $userId,
            'status'           => 'registered',
        ]);
    }

    public function unregister(WorkshopAttendee $attendee): bool
    {
        return $attendee->update(['status' => 'cancelled']);
    }
}