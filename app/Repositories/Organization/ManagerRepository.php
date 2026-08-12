<?php

namespace App\Repositories\Organization;

use Illuminate\Support\Facades\DB;

class ManagerRepository
{
    /**
     * يرجّع مدير فرع معيّن، بشرط إنه فعليًا تابع لفروع هاد الـ Owner
     * (عبر السلسلة: owner → companies → branches → users).
     */
    public function findForOwner(int $managerId, int $ownerUserId): ?object
    {
        return DB::table('users')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->join('companies', 'branches.company_id', '=', 'companies.id')
            ->where('companies.owner_user_id', $ownerUserId)
            ->where('users.id', $managerId)
            ->where('users.user_type', 'manager')
            ->whereNull('users.deleted_at')
            ->select('users.*')
            ->first();
    }

    /**
     * يرجّع كل مدراء الفروع التابعين لهاد الـ Owner (لعرضهم بقائمة اختيار مثلاً).
     */
    public function listForOwner(int $ownerUserId)
    {
        return DB::table('users')
            ->join('branches', 'users.branch_id', '=', 'branches.id')
            ->join('companies', 'branches.company_id', '=', 'companies.id')
            ->where('companies.owner_user_id', $ownerUserId)
            ->where('users.user_type', 'manager')
            ->whereNull('users.deleted_at')
            ->select('users.*')
            ->get();
    }
}