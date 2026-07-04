<?php

namespace App\Repositories\Hr;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SupervisorDashboardRepository
{
    /**
     * جلب معرفات المستخدمين للموظفين التابعين للمشرف المباشر
     * (بعد إضافة حقل supervisor_id من المايجريشن)
     */
    public function getTeamUserIds(int $supervisorUserId): array
    {
        return DB::table('employee_details')
            ->where('supervisor_id', $supervisorUserId)
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * حساب الأرقام الموجودة في الكاردات الإحصائية
     */
    public function getStatisticsCounts(array $teamUserIds): array
    {
        $today = Carbon::today()->toDateString();
        $totalEmployees = count($teamUserIds);

        // تعديل 1: استخدام الجدول الصحيح attendance_logs والحقل employee_user_id
        $presentToday = DB::table('attendance_logs')
            ->whereIn('employee_user_id', $teamUserIds)
            ->whereDate('check_in', $today)
            ->where('status', 'present') // نحسب الحاضرين فقط (بدون المتأخرين في هذه البطاقة)
            ->count();

        // تعديل 2: نفس التعديل للغياب
        $absentToday = DB::table('attendance_logs')
            ->whereIn('employee_user_id', $teamUserIds)
            ->whereDate('check_in', $today)
            ->where('status', 'absent')
            ->count();

        // تعديل 3: استخدام الحقل employee_user_id بدل assigned_to
        $pendingTasks = DB::table('tasks')
            ->whereIn('employee_user_id', $teamUserIds)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        // تعديل 4: التأكد من استخدام employee_id (كما هو في الـ Schema الخاص بك)
        $pendingLeaves = DB::table('leave_requests')
            ->whereIn('employee_id', $teamUserIds)
            ->where('status', 'pending') // لا نحسب pending_manager هنا، نريد التي تنتظر المشرف فقط
            ->count();

        // تعديل 5: استخدام employee_user_id
        $avgEvaluation = DB::table('performance_evaluations')
            ->whereIn('employee_user_id', $teamUserIds)
            ->where('status', 'completed') // أو reviewed حسب منطقك
            ->avg('overall_score') ?? 0;

        return [
            'total_employees' => $totalEmployees,
            'present_today' => $presentToday,
            'absent_today' => $absentToday,
            'pending_tasks' => $pendingTasks,
            'pending_leaves' => $pendingLeaves,
            'avg_evaluation' => round($avgEvaluation, 2)
        ];
    }

    /**
     * رسم بياني للحضور الأسبوعي (آخر 7 أيام)
     */
    public function getWeeklyAttendanceChart(array $teamUserIds): array
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            
            // تعديل 6: استخدام attendance_logs و employee_user_id و check_in
            $days[$date->format('Y-m-d')] = DB::table('attendance_logs')
                ->whereIn('employee_user_id', $teamUserIds)
                ->whereDate('check_in', $date->toDateString())
                ->whereIn('status', ['present', 'late']) // عادة المتأخر يحسب في رسم الحضور
                ->count();
        }
        return $days;
    }

    /**
     * رسم بياني لتوزيع حالات المهام (Pie Chart)
     */
    public function getTasksDistributionChart(array $teamUserIds): array
    {
        // تعديل 7: استخدام employee_user_id
        return DB::table('tasks')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereIn('employee_user_id', $teamUserIds)
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * آخر 5 مهام متأخرة
     */
    public function getOverdueTasks(array $teamUserIds): \Illuminate\Support\Collection
    {
        // تعديل 8: استخدام employee_user_id
        return DB::table('tasks')
            ->join('user_profiles', 'tasks.employee_user_id', '=', 'user_profiles.user_id')
            ->whereIn('tasks.employee_user_id', $teamUserIds)
            ->whereIn('tasks.status', ['pending', 'in_progress'])
            ->where('tasks.due_date', '<', Carbon::now())
            ->select('tasks.id', 'tasks.title', 'user_profiles.full_name as employee_name', 'tasks.due_date')
            ->orderBy('tasks.due_date', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($task) {
                $task->days_delayed = Carbon::parse($task->due_date)->diffInDays(Carbon::now());
                // تطبيق قاعدة التواريخ الصارمة
                $task->due_date = Carbon::parse($task->due_date)->format('Y-m-d H:i:s');
                return $task;
            });
    }

    /**
     * آخر 5 طلبات إجازة
     */
    public function getRecentLeaveRequests(array $teamUserIds): \Illuminate\Support\Collection
    {
        return DB::table('leave_requests')
            ->join('user_profiles', 'leave_requests.employee_id', '=', 'user_profiles.user_id')
            // تعديل 9: ربط مع جدول leave_types لأخذ اسم النوع بدل الـ ID
            ->leftJoin('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->whereIn('leave_requests.employee_id', $teamUserIds)
            ->select(
                'leave_requests.id', 
                'user_profiles.full_name as employee_name', 
                'leave_types.name as type', // افترضت أن اسم النوع في جدول leave_types هو 'name'
                'leave_requests.start_date', 
                'leave_requests.end_date', 
                'leave_requests.status'
            )
            ->orderBy('leave_requests.created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($leave) {
                $leave->start_date = Carbon::parse($leave->start_date)->format('Y-m-d');
                $leave->end_date = Carbon::parse($leave->end_date)->format('Y-m-d');
                return $leave;
            });
    }
}