<?php

namespace App\Services\Supervisor;

use App\Repositories\Interfaces\Supervisor\ProfileRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function __construct(
        private ProfileRepositoryInterface $repository,
    ) {}

    public function get(int $userId): array
    {
        $profile = $this->repository->getProfile($userId);

        if (!$profile) {
            throw ValidationException::withMessages(['profile' => ['Profile not found.']]);
        }

        return $this->format($profile);
    }

    public function updateAvatar(int $userId, $avatarFile): array
    {
        $oldAvatar = DB::table('user_profiles')->where('user_id', $userId)->value('avatar');

        $avatarPath = $avatarFile->store('avatars', 'public');

        try {
            $this->repository->updateAvatar($userId, $avatarPath);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($avatarPath);
            throw $e;
        }

        if ($oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return $this->get($userId);
    }

    private function format(object $p): array
    {
        return [
            'id' => $p->id,
            'full_name' => $p->full_name,
            'role' => $p->user_type,
            'department_name' => $p->department_name,
            'branch_name' => $p->branch_name,
            'phone' => $p->phone,
            'avatar' => $p->avatar ? Storage::url($p->avatar) : null,
        ];
    }
}