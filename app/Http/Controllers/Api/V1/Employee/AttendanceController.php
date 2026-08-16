<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\CheckQrCodeRequest;
use App\Services\Hr\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Hr\AttendanceLog;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
    ) {}

    public function checkIn(CheckQrCodeRequest $request): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $result = $this->attendanceService->checkIn($userId, $request->validated()['qr_code']);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], $result['code']);
        }

        return response()->json(['success' => true, 'message' => 'Checked in successfully.', 'data' => $result['data']]);
    }

    public function checkOut(CheckQrCodeRequest $request): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $result = $this->attendanceService->checkOut($userId, $request->validated()['qr_code']);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], $result['code']);
        }

        return response()->json(['success' => true, 'message' => 'Checked out successfully.', 'data' => $result['data']]);
    }
    public function getAttendanceHistory(Request $request)
    {
        $employeeId = Auth::id(); // الموظف المسجل دخوله حالياً

        // 1. تحديد النطاق الزمني للشهر الحالي تلقائياً (30 أو 31 يوم أو 28/29 لشهر شباط)
        $startDate = $request->query('start_date') 
            ? Carbon::parse($request->query('start_date'))->startOfDay() 
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = $request->query('end_date') 
            ? Carbon::parse($request->query('end_date'))->endOfDay() 
            : Carbon::now()->endOfMonth()->endOfDay();

        // 2. جلب سجلات الحضور الخاصة بالشهر المعتمد
        $monthlyLogs = AttendanceLog::where('employee_user_id', $employeeId)
            ->whereBetween('check_in', [$startDate, $endDate])
            ->orderBy('check_in', 'desc')
            ->get();

        // 3. حساب الكروت الإحصائية للشهر الحالي
        $workingDays = $monthlyLogs->whereIn('status', ['present', 'late', 'early_leave'])->count();
        $absentDays = $monthlyLogs->where('status', 'absent')->count();

        // حساب ساعات التأخير (بناءً على السجلات المعلّمة بـ late)
        // يتم حساب الفارق بين check_in و بدايه الشفت الرسمي (مثلاً 09:00:00)
        $shiftStartTime = "09:00:00"; 
        $totalLateMinutes = 0;

        foreach ($monthlyLogs->where('status', 'late') as $log) {
            $checkIn = Carbon::parse($log->check_in);
            $scheduledTime = Carbon::parse($checkIn->toDateString() . ' ' . $shiftStartTime);
            
            if ($checkIn->gt($scheduledTime)) {
                $totalLateMinutes += $scheduledTime->diffInMinutes($checkIn);
            }
        }

        $lateHours = floor($totalLateMinutes / 60);
        $lateMinutes = $totalLateMinutes % 60;
        $formattedLateHours = "{$lateHours}h {$lateMinutes}m";

        // 4. بناء بيانات الرسم البياني للأشهر الـ 6 الأخيرة (Monthly Working Days Chart)
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $daysCount = AttendanceLog::where('employee_user_id', $employeeId)
                ->whereIn('status', ['present', 'late', 'early_leave'])
                ->whereBetween('check_in', [$monthStart, $monthEnd])
                ->count();

            $chartData[] = [
                'month' => $month->format('M'), // Jan, Feb, Mar...
                'year' => $month->year,
                'working_days' => $daysCount,
            ];
        }

        // 5. تنسيق القائمة اليومية (Daily Records)
        $dailyRecords = $monthlyLogs->map(function ($log) {
            $checkIn = Carbon::parse($log->check_in);
            $checkOut = $log->check_out ? Carbon::parse($log->check_out) : null;
            
            // حساب مدة العمل اليومية بالساعات والدقائق
            $workDuration = "0h 0m";
            if ($checkOut) {
                $diffMinutes = $checkIn->diffInMinutes($checkOut);
                $workDuration = floor($diffMinutes / 60) . "h " . ($diffMinutes % 60) . "m";
            }

            return [
                'id' => $log->id,
                'day_name' => $checkIn->format('D, M d'), // Thu, Jun 19
                'date' => $checkIn->toDateString(),
                'time_range' => $checkOut 
                    ? $checkIn->format('H:i') . ' - ' . $checkOut->format('H:i') 
                    : $checkIn->format('H:i') . ' - --:--',
                'work_duration' => $workDuration,
                'status' => $log->status, // present, late, absent, early_leave
            ];
        });

        // 6. إرجاع الـ Response بتنسيق جاهز للواجهة
        return response()->json([
            'status' => true,
            'data' => [
                'date_range' => [
                    'start_date' => $startDate->format('M d, Y'), // Jun 1, 2026
                    'end_date' => $endDate->format('M d, Y'),     // Jun 30, 2026
                ],
                'stats' => [
                    'working_days' => $workingDays,
                    'absent_days' => $absentDays,
                    'late_hours' => $formattedLateHours,
                ],
                'monthly_chart' => $chartData,
                'daily_records' => $dailyRecords,
            ]
        ], 200);
    }
}