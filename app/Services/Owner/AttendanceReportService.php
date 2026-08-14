<?php

namespace App\Services\Owner;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceReportService
{
    public function getData(int $companyId, array $filters): array
    {
        [$from, $to] = $this->resolvePeriod($filters['period'] ?? 'monthly');

        $query = DB::table('attendance_logs as a')
            ->join('users as u', 'u.id', '=', 'a.employee_user_id')
            ->join('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->join('branches as b', 'b.id', '=', 'a.branch_id')
            ->where('a.company_id', $companyId)
            ->whereBetween('a.check_in', [$from, $to])
            ->whereNull('a.deleted_at');

        if (!empty($filters['branch_id'])) {
            $query->where('a.branch_id', $filters['branch_id']);
        }

        $records = $query->select([
            'p.full_name as employee_name',
            'b.name as branch_name',
            'a.check_in',
            'a.check_out',
            'a.work_hours',
            'a.status',
            'a.type',
        ])->orderByDesc('a.check_in')->get();

        $totalPresent = $records->where('status', 'present')->count();
        $totalLate = $records->where('status', 'late')->count();
        $totalAbsent = $records->where('status', 'absent')->count();
        $totalEarlyLeave = $records->where('status', 'early_leave')->count();
        $totalWithStatus = $totalPresent + $totalLate + $totalAbsent + $totalEarlyLeave;

        $attendanceRate = $totalWithStatus > 0
            ? round((($totalPresent + $totalLate) / $totalWithStatus) * 100, 2)
            : 0;

        // بيانات الرسم البياني: توزيع الحضور حسب الفرع
        $chartData = DB::table('attendance_logs as a')
            ->join('branches as b', 'b.id', '=', 'a.branch_id')
            ->where('a.company_id', $companyId)
            ->whereBetween('a.check_in', [$from, $to])
            ->whereIn('a.status', ['present', 'late'])
            ->whereNull('a.deleted_at')
            ->select('b.name', DB::raw('COUNT(DISTINCT a.employee_user_id) as count'))
            ->groupBy('b.name')
            ->pluck('count', 'name')
            ->toArray();

        return [
            'period' => $filters['period'] ?? 'monthly',
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'summary' => [
                'total_present' => $totalPresent,
                'total_late' => $totalLate,
                'total_absent' => $totalAbsent,
                'total_early_leave' => $totalEarlyLeave,
                'attendance_rate' => $attendanceRate,
            ],
            'chart' => $chartData,
            'records' => $records->map(fn($r) => [
                'employee_name' => $r->employee_name,
                'branch_name' => $r->branch_name,
                'date' => Carbon::parse($r->check_in)->format('Y-m-d'),
                'check_in' => Carbon::parse($r->check_in)->format('H:i:s'),
                'check_out' => $r->check_out ? Carbon::parse($r->check_out)->format('H:i:s') : null,
                'work_hours' => $r->work_hours,
                'status' => $r->status,
                'type' => $r->type,
            ])->all(),
        ];
    }

    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            'daily' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'weekly' => [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()],
            'monthly' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }
}