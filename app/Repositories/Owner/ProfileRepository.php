<?php

namespace App\Repositories\Owner;

use App\Repositories\Interfaces\Owner\ProfileRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProfileRepository implements ProfileRepositoryInterface
{
    public function getProfile(int $userId): ?object
    {
        return DB::table('users as u')
            ->join('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->leftJoin('companies as c', 'c.id', '=', 'u.company_id')
            ->where('u.id', $userId)
            ->whereNull('u.deleted_at')
            ->select([
                'u.id', 'u.phone', 'u.email', 'u.user_type', 'u.created_at',
                'p.full_name', 'p.avatar', 'p.national_id', 'p.date_of_birth',
                'p.gender', 'p.bio', 'p.address',
                'c.name as company_name',
            ])
            ->first();
    }

    public function updateProfile(int $userId, array $userData, array $profileData): void
    {
        if (!empty($userData)) {
            $userData['updated_at'] = now();
            DB::table('users')->where('id', $userId)->update($userData);
        }

        if (!empty($profileData)) {
            $profileData['updated_at'] = now();
            DB::table('user_profiles')->where('user_id', $userId)->update($profileData);
        }
    }
}