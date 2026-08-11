<?php

namespace App\Repositories\Hr;

use App\Models\Hr\Workshop;
use App\Models\Hr\WorkshopAttendee;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkshopRepository
{
    // ================= دوال Branch Manager (جديدة) =================

    public function paginateForBranch(int $branchId, array $filters, int $perPage = 15)
    {
        $query = DB::table('workshops')
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->select(
                'id', 'title', 'start_date', 'end_date', 'location',
                'capacity', 'status', 'created_at'
            )
            ->selectSub(function ($q) {
                $q->from('workshop_attendees')
                  ->selectRaw('COUNT(*)')
                  ->whereColumn('workshop_id', 'workshops.id')
                  ->where('status', 'registered');
            }, 'registered_count');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('start_date')->paginate($perPage);
    }

    public function findForBranch(int $id, int $branchId): ?object
    {
        return DB::table('workshops')
            ->where('id', $id)
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function create(array $data): int
    {
        return DB::table('workshops')->insertGetId([
            'company_id' => $data['company_id'],
            'branch_id' => $data['branch_id'],
            'created_by' => $data['created_by'],
            'title' => $data['title'],
            'description' => $data['description'],
            'location' => $data['location'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'capacity' => $data['capacity'],
            'status' => 'upcoming',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function update(int $id, array $data): void
    {
        $data['updated_at'] = Carbon::now();
        DB::table('workshops')->where('id', $id)->update($data);
    }

    public function getAttendeesForWorkshop(int $workshopId): array
    {
        return DB::table('workshop_attendees')
            ->join('user_profiles', 'workshop_attendees.employee_user_id', '=', 'user_profiles.user_id')
            ->join('employee_details', 'workshop_attendees.employee_user_id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->where('workshop_attendees.workshop_id', $workshopId)
            ->where('workshop_attendees.status', '!=', 'cancelled')
            ->select(
                'workshop_attendees.id',
                'workshop_attendees.employee_user_id',
                'workshop_attendees.status',
                'workshop_attendees.registered_at',
                'user_profiles.full_name as employee_name',
                'departments.name as department_name'
            )
            ->get()
            ->toArray();
    }

    public function findAttendeeRecord(int $workshopId, int $employeeUserId): ?object
    {
        return DB::table('workshop_attendees')
            ->where('workshop_id', $workshopId)
            ->where('employee_user_id', $employeeUserId)
            ->first();
    }

    public function updateAttendeeStatus(int $attendeeId, string $status): void
    {
        DB::table('workshop_attendees')->where('id', $attendeeId)->update([
            'status' => $status,
            'updated_at' => Carbon::now(),
        ]);
    }

    public function cancelAllRegistrations(int $workshopId): void
    {
        DB::table('workshop_attendees')
            ->where('workshop_id', $workshopId)
            ->where('status', 'registered')
            ->update(['status' => 'cancelled', 'updated_at' => Carbon::now()]);
    }

    // ================= دوال Employee Mobile (الأصلية — لم تُمس) =================

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