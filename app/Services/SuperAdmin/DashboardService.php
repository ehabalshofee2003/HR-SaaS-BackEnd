<?php

namespace App\Services\SuperAdmin;

use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getData(): array
    {
        $totalCompanies = DB::table('companies')->whereNull('deleted_at')->count();
        $activeCompanies = DB::table('companies')->where('status', 'active')->whereNull('deleted_at')->count();
        $suspendedCompanies = DB::table('companies')->where('status', 'suspended')->whereNull('deleted_at')->count();

        $totalEmployees = DB::table('users')->where('user_type', 'employee')->whereNull('deleted_at')->count();

        $monthlyRevenue = (float) DB::table('company_subscriptions as cs')
            ->join('subscription_plans as sp', 'sp.id', '=', 'cs.plan_id')
            ->where('cs.status', 'active')
            ->sum('sp.price');

        $expiringSoon = DB::table('company_subscriptions')
            ->where('status', 'active')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->count();

        $recentCompanies = DB::table('companies as c')
            ->leftJoin('company_subscriptions as cs', function ($j) {
                $j->on('cs.company_id', '=', 'c.id')->where('cs.status', 'active');
            })
            ->leftJoin('subscription_plans as sp', 'sp.id', '=', 'cs.plan_id')
            ->whereNull('c.deleted_at')
            ->orderByDesc('c.created_at')
            ->limit(10)
            ->get(['c.id', 'c.name', 'sp.name as plan_name', 'c.status', 'c.created_at']);

        return [
            'stats' => [
                'total_companies' => $totalCompanies,
                'active_companies' => $activeCompanies,
                'suspended_companies' => $suspendedCompanies,
                'monthly_revenue' => $monthlyRevenue,
                'total_employees' => $totalEmployees,
                'expiring_subscriptions' => $expiringSoon,
            ],
            'recent_companies' => $recentCompanies->all(),
        ];
    }
}