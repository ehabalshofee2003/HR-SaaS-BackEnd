<?php

namespace App\Repositories\SuperAdmin;

use App\Repositories\Interfaces\SuperAdmin\CompanyRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function list(array $filters): array
    {
        $query = DB::table('companies as c')
            ->join('users as u', 'u.id', '=', 'c.owner_user_id')
            ->join('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->leftJoin('company_subscriptions as cs', function ($join) {
                $join->on('cs.company_id', '=', 'c.id')->where('cs.status', 'active');
            })
            ->leftJoin('subscription_plans as sp', 'sp.id', '=', 'cs.plan_id')
            ->whereNull('c.deleted_at')
            ->select([
                'c.id', 'c.name', 'c.status', 'c.logo', 'c.created_at',
                'p.full_name as owner_name', 'u.email as owner_email', 'u.phone as owner_phone',
                'sp.name as plan_name',
            ]);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('c.name', 'like', "%{$s}%")
                  ->orWhere('p.full_name', 'like', "%{$s}%")
                  ->orWhere('u.email', 'like', "%{$s}%")
                  ->orWhere('u.phone', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('c.status', $filters['status']);
        }

        if (!empty($filters['plan_id'])) {
            $query->where('sp.id', $filters['plan_id']);
        }

        $sortBy = $filters['sort_by'] ?? 'c.created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);
        $page = (int) ($filters['page'] ?? 1);
        $total = (clone $query)->count();

        return [
            'data' => $query->forPage($page, $perPage)->get()->all(),
            'meta' => [
                'total' => $total,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
                'per_page' => $perPage,
            ],
        ];
    }

    public function find(int $id): ?object
    {
        return DB::table('companies as c')
            ->join('users as u', 'u.id', '=', 'c.owner_user_id')
            ->join('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->leftJoin('company_subscriptions as cs', function ($join) {
                $join->on('cs.company_id', '=', 'c.id')->where('cs.status', 'active');
            })
            ->leftJoin('subscription_plans as sp', 'sp.id', '=', 'cs.plan_id')
            ->where('c.id', $id)
            ->whereNull('c.deleted_at')
            ->select([
                'c.id', 'c.name', 'c.status', 'c.logo', 'c.industry', 'c.website', 'c.created_at',
                'u.id as owner_id', 'p.full_name as owner_name', 'u.email as owner_email', 'u.phone as owner_phone',
                'sp.name as plan_name', 'cs.start_date', 'cs.end_date', 'cs.status as subscription_status',
            ])
            ->first();
    }

    public function emailOrPhoneExists(string $phone, ?string $email): bool
    {
        $query = DB::table('users')->where('phone', $phone)->whereNull('deleted_at');

        if ($email) {
            $query->orWhere(fn($q) => $q->where('email', $email)->whereNull('deleted_at'));
        }

        return $query->exists();
    }

    public function createOwner(array $data): int
    {
        return DB::table('users')->insertGetId([
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password_hash' => $data['password_hash'],
            'user_type' => 'owner',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createCompany(array $data): int
    {
        $companyId = DB::table('companies')->insertGetId([
            'owner_user_id' => $data['owner_user_id'],
            'name' => $data['name'],
            'status' => 'active',
            'industry' => $data['industry'] ?? null,
            'website' => $data['website'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $data['owner_user_id'])->update(['company_id' => $companyId]);
        DB::table('user_profiles')->insert([
            'user_id' => $data['owner_user_id'],
            'full_name' => $data['owner_name'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $companyId;
    }

    public function createSubscription(array $data): void
    {
        DB::table('company_subscriptions')->insert([
            'company_id' => $data['company_id'],
            'plan_id' => $data['plan_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'auto_renew' => false,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function update(int $id, array $data): void
    {
        if (empty($data)) {
            return;
        }

        $data['updated_at'] = now();
        DB::table('companies')->where('id', $id)->update($data);
    }

    public function softDelete(int $id): void
    {
        DB::table('companies')->where('id', $id)->update(['deleted_at' => now()]);
    }

    public function branchesCount(int $companyId): int
    {
        return DB::table('branches')->where('company_id', $companyId)->whereNull('deleted_at')->count();
    }

    public function employeesCount(int $companyId): int
    {
        return DB::table('users')->where('company_id', $companyId)->where('user_type', 'employee')->whereNull('deleted_at')->count();
    }
}