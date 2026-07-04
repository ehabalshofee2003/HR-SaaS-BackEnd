<?php

namespace App\Services\Hr;

use App\Models\Identity\User;
use App\Repositories\Hr\SupervisorDashboardRepository;
use App\Http\Resources\Supervisor\DashboardResource;

class SupervisorDashboardService
{
    public function __construct(
        private SupervisorDashboardRepository $repository
    ) {}

    public function getDashboardData(User $user): array
    {
        // 1. جلب معرفات موظفي القسم التابعين للمشرف فقط
        $teamUserIds = $this->repository->getTeamUserIds($user->id);
        
        // 2. جلب الإحصائيات الأساسية
        $stats = $this->repository->getStatisticsCounts($teamUserIds);
        
        // 3. حساب نسبة الحضور (تجنب القسمة على صفر)
        $stats['attendance_percentage'] = $stats['total_employees'] > 0 
            ? round(($stats['present_today'] / $stats['total_employees']) * 100, 2) 
            : 0;

        // 4. جلب بيانات الرسوم البيانية (Charts)
        $weeklyAttendance = $this->repository->getWeeklyAttendanceChart($teamUserIds);
        $tasksDistribution = $this->repository->getTasksDistributionChart($teamUserIds);

        // 5. جلب القوائم (آخر 5 مهام متأخرة، آخر 5 طلبات إجازة)
        $overdueTasks = $this->repository->getOverdueTasks($teamUserIds);
        $recentLeaves = $this->repository->getRecentLeaveRequests($teamUserIds);

        // 6. صلاحيات الواجهة الديناميكية (للفرونت)
        $permissions = $user->permissions ?? []; // افتراض أن هناك علاقة permissions على الموديل

        // 7. تمرير البيانات للـ Resource لتوحيد الشكل النهائي
        return (new DashboardResource([
            'stats' => $stats,
            'weekly_attendance' => $weeklyAttendance,
            'tasks_distribution' => $tasksDistribution,
            'overdue_tasks' => $overdueTasks,
            'recent_leaves' => $recentLeaves,
            'permissions' => $permissions
        ]))->resolve();
    }
}