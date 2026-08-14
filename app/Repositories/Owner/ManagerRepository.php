<?php

namespace App\Repositories\Owner;

use App\Repositories\Interfaces\Owner\ManagerRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ManagerRepository implements ManagerRepositoryInterface
{
    public function list(int $companyId, array $filters = []): array
    {
        $query = DB::table('users')
            ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->leftJoin('branches', 'branches.id', '=', 'users.branch_id')
            ->where('users.company_id', $companyId)
            ->where('users.user_type', 'manager')
            ->whereNull('users.deleted_at')
            ->select([
                'users.id',
                'user_profiles.full_name as name',
                'users.phone',
                'users.email',
                'user_profiles.avatar',
                'users.status',
                'branches.id as branch_id',
                'branches.name as branch_name',
                'users.created_at',
            ]);

        if (!empty($filters['status'])) {
            $query->where('users.status', $filters['status']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('users.branch_id', $filters['branch_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('user_profiles.full_name', 'like', "%{$search}%")
                  ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('users.id')->get()->all();
    }

    public function find(int $id, int $companyId): ?object
    {
        return DB::table('users')
            ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->leftJoin('branches', 'branches.id', '=', 'users.branch_id')
            ->where('users.id', $id)
            ->where('users.company_id', $companyId)
            ->where('users.user_type', 'manager')
            ->whereNull('users.deleted_at')
            ->select([
                'users.id',
                'user_profiles.full_name as name',
                'users.phone',
                'users.email',
                'user_profiles.avatar',
                'users.status',
                'branches.id as branch_id',
                'branches.name as branch_name',
                'users.created_at',
            ])
            ->first();
    }

    public function phoneExists(string $phone, ?int $excludeId = null): bool
    {
        $query = DB::table('users')->where('phone', $phone)->whereNull('deleted_at');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = DB::table('users')->where('email', $email)->whereNull('deleted_at');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function branchHasManager(int $branchId, ?int $excludeId = null): bool
    {
        $query = DB::table('users')
            ->where('branch_id', $branchId)
            ->where('user_type', 'manager')
            ->whereNull('deleted_at');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function branchBelongsToCompany(int $branchId, int $companyId): bool
    {
        return DB::table('branches')
            ->where('id', $branchId)
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function createUser(array $data): int
    {
        return DB::table('users')->insertGetId([
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password_hash' => $data['password_hash'],
            'user_type' => 'manager',
            'status' => 'active',
            'company_id' => $data['company_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createProfile(array $data): void
    {
        DB::table('user_profiles')->insert([
            'user_id' => $data['user_id'],
            'full_name' => $data['full_name'],
            'avatar' => $data['avatar'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function updateUser(int $id, array $data): void
    {
        if (empty($data)) {
            return;
        }

        $data['updated_at'] = now();
        DB::table('users')->where('id', $id)->update($data);
    }

    public function updateProfile(int $id, array $data): void
    {
        if (empty($data)) {
            return;
        }

        $data['updated_at'] = now();
        DB::table('user_profiles')->where('user_id', $id)->update($data);
    }

    public function softDelete(int $id): void
    {
        DB::table('users')->where('id', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);
    }
}