<?php

namespace App\Services\Employee;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceSummaryService
{
    public function getData(int $employeeUserId, int $companyId): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $today = Carbon::today();

        $offDays = $this->getWeeklyOffDays($companyId);

        $logs = DB::table('attendance_logs')
            ->where('employee_user_id', $employeeUserId)
            ->whereBetween('check_in', [$monthStart, $monthEnd])
            ->whereNull('deleted_at')
            ->get();

        $workingDays = $logs->whereIn('status', ['present', 'late'])->pluck('check_in')
            ->map(fn($d) => Carbon::parse($d)->toDateString())->unique()->count();

        $businessDaysSoFar = 0;
        $cursor = $monthStart->copy();
        while ($cursor->lte($today)) {
            if (!in_array(strtolower($cursor->englishDayOfWeek), $offDays)) {
                $businessDaysSoFar++;
            }
            $cursor->addDay();
        }

        $absentDays = max(0, $businessDaysSoFar - $workingDays);

        $lateMinutesTotal = 0;
        foreach ($logs->where('status', 'late') as $log) {
            $officialStart = Carbon::parse($log->check_in)->copy()->setTime(8, 0);
            $checkIn = Carbon::parse($log->check_in);
            if ($checkIn->gt($officialStart)) {
                $lateMinutesTotal += $officialStart->diffInMinutes($checkIn);
            }
        }
        $lateHours = intdiv($lateMinutesTotal, 60) . 'h ' . ($lateMinutesTotal % 60) . 'm';

        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i);
            $count = DB::table('attendance_logs')
                ->where('employee_user_id', $employeeUserId)
                ->whereYear('check_in', $m->year)
                ->whereMonth('check_in', $m->month)
                ->whereIn('status', ['present', 'late'])
                ->whereNull('deleted_at')
                ->distinct()
                ->count(DB::raw('DATE(check_in)'));

            $monthlyTrend[] = ['month' => $m->format('M'), 'working_days' => $count];
        }

        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weeklyRecords = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $isOff = in_array(strtolower($date->englishDayOfWeek), $offDays);

            $log = $logs->first(fn($l) => Carbon::parse($l->check_in)->isSameDay($date))
                ?? DB::table('attendance_logs')
                    ->where('employee_user_id', $employeeUserId)
                    ->whereDate('check_in', $date)
                    ->whereNull('deleted_at')
                    ->first();

            if ($isOff) {
                $status = 'day_off';
            } elseif ($log) {
                $status = $log->status;
            } elseif ($date->isFuture()) {
                $status = 'upcoming';
            } else {
                $status = 'absent';
            }

            $weeklyRecords[] = [
                'date' => $date->toDateString(),
                'day_label' => $date->format('D'),
                'check_in' => $log ? Carbon::parse($log->check_in)->format('H:i') : null,
                'check_out' => $log && $log->check_out ? Carbon::parse($log->check_out)->format('H:i') : null,
                'work_hours' => $log ? (float) $log->work_hours : 0,
                'status' => $status,
            ];
        }

        return [
            'period' => ['from' => $monthStart->toDateString(), 'to' => $monthEnd->toDateString()],
            'stats' => [
                'working_days' => $workingDays,
                'absent_days' => $absentDays,
                'late_hours' => $lateHours,
            ],
            'monthly_trend' => $monthlyTrend,
            'weekly_records' => $weeklyRecords,
        ];
    }

    private function getWeeklyOffDays(int $companyId): array
    {
        $setting = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->where('key', 'weekly_off_days')
            ->value('value');

        return $setting ? json_decode($setting, true) : ['friday'];
    }
}