<?php

namespace App\Repositories\Hr;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SupervisorEmployeeRepository
{
    public function canAddEmployee(int $companyId): bool
    {
        $subscription = DB::table('company_subscriptions')
            ->join('subscription_plans', 'company_subscriptions.plan_id', '=', 'subscription_plans.id')
            ->where('company_subscriptions.company_id', $companyId)
            ->where('company_subscriptions.status', 'active')
            ->where('company_subscriptions.end_date', '>=', Carbon::now())
            ->select('subscription_plans.max_employees')
            ->first();

        if (!$subscription || !$subscription->max_employees) return true;

        $currentEmployees = DB::table('employee_details')
            ->where('department_id', function($query) use ($companyId) {
                $query->select('id')->from('departments')->where('company_id', $companyId);
            })->count();

        return $currentEmployees < $subscription->max_employees;
    }
    public function getEmployeesList(int $supervisorId, array $filters)
    {
        // 1. نجرب أولاً استعلام بسيط جداً بدون أي Joins معقدة لنتأكد أن الموظفين موجودين أساساً
        $simpleTest = DB::table('employee_details')
            ->where('supervisor_id', $supervisorId)
            ->count();

        // إذا كان العدد صفر، فهذا يعني أن المشرف لا يملك موظفين فعلياً تحت إدارته في الداتا بيز
        if ($simpleTest === 0) {
            // نرجع paginator فارغ بصيغة صحيحة
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1);
        }

        // 2. إذا كان هناك موظفون، ننفذ الاستعلام الكامل مع حماية الـ Joins
        $today = Carbon::today()->toDateString();

        $tasksCountSub = DB::table('tasks')
            ->select('employee_user_id', DB::raw('COUNT(id) as pending_tasks_count'))
            ->whereIn('status', ['pending', 'in_progress'])
            ->groupBy('employee_user_id');

        $query = DB::table('users')
            // استخدمنا leftJoin بدل join لحماية القائمة في حال غياب الـ profile
            ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->join('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->leftJoinSub($tasksCountSub, 'tasks_agg', function($join) {
                $join->on('users.id', '=', 'tasks_agg.employee_user_id');
            })
            ->leftJoin('attendance_logs', function($join) use ($today) {
                $join->on('users.id', '=', 'attendance_logs.employee_user_id')
                     ->whereDate('attendance_logs.check_in', $today);
            })
            ->where('employee_details.supervisor_id', $supervisorId)
            ->select(
                'users.id', 'users.phone', 'users.created_at',
                'user_profiles.full_name', 'user_profiles.avatar',
                'employee_details.job_title', 'employee_details.basic_salary', 
                'employee_details.contract_type', 'employee_details.employment_status',
                'attendance_logs.status as today_status', 
                'attendance_logs.check_in as today_check_in',
                'tasks_agg.pending_tasks_count'
            );

        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('user_profiles.full_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('users.phone', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['employment_status'])) {
            $query->where('employee_details.employment_status', $filters['employment_status']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        
        $allowedSorts = ['created_at', 'full_name', 'basic_salary', 'today_status'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        if ($sortBy === 'full_name') $sortBy = 'user_profiles.full_name';
        if ($sortBy === 'basic_salary') $sortBy = 'employee_details.basic_salary';

        $query->orderBy($sortBy, $sortDir);

        return $query->paginate(15);
    }

    public function getEmployeeWithDetails(int $supervisorId, int $userId)
    {
        return DB::table('users')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->join('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->leftJoin('payroll_records', 'users.id', '=', 'payroll_records.employee_user_id')
            ->where('employee_details.supervisor_id', $supervisorId)
            ->where('users.id', $userId)
            ->select(
                'users.id', 'users.phone', 'users.email', // أضفنا email هنا
                'user_profiles.full_name', 'user_profiles.avatar', 'user_profiles.national_id', 'user_profiles.date_of_birth',
                'employee_details.job_title', 'employee_details.basic_salary', 'employee_details.contract_type', 
                'employee_details.employment_status', 'employee_details.hire_date',
                'payroll_records.net_salary as last_net_salary'
            )
            ->orderBy('payroll_records.created_at', 'desc')
            ->first();
    }
        public function getSupervisorRawData(int $supervisorId)
    {
        return DB::table('users')
            ->join('employee_details', 'users.id', '=', 'employee_details.user_id')
            ->join('departments', 'employee_details.department_id', '=', 'departments.id')
            ->join('branches', 'departments.branch_id', '=', 'branches.id')
            ->where('users.id', $supervisorId)
            ->select('departments.id as department_id', 'branches.company_id')
            ->first();
    }
    public function isEmployeeInTeam(int $supervisorId, int $employeeId): bool
    {
        return DB::table('employee_details')
            ->where('user_id', $employeeId)
            ->where('supervisor_id', $supervisorId)
            ->exists();
    }
}