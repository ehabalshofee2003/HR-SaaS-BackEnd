<?php

namespace App\Repositories\Supervisor;

use App\Repositories\Interfaces\Supervisor\ProfileRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProfileRepository implements ProfileRepositoryInterface
{
    public function getProfile(int $userId): ?object
    {
        $user = DB::table('users as u')
            ->join('user_profiles as p', 'p.user_id', '=', 'u.id')
            ->where('u.id', $userId)
            ->whereNull('u.deleted_at')
            ->select(['u.id', 'u.phone', 'u.user_type', 'u.branch_id', 'p.full_name', 'p.avatar'])
            ->first();

        if (!$user) {
            return null;
        }

        $department = DB::table('departments')
            ->where('supervisor_user_id', $userId)
            ->whereNull('deleted_at')
            ->first(['id', 'name']);

        $branch = $user->branch_id
            ? DB::table('branches')->where('id', $user->branch_id)->whereNull('deleted_at')->first(['id', 'name'])
            : null;

        $user->department_name = $department->name ?? null;
        $user->branch_name = $branch->name ?? null;

        return $user;
    }

    public function updateAvatar(int $userId, string $avatarPath): void
    {
        DB::table('user_profiles')->where('user_id', $userId)->update([
            'avatar' => $avatarPath,
            'updated_at' => now(),
        ]);
    }
}