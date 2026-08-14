<?php

namespace App\Services\Owner;

use App\Repositories\Interfaces\Owner\ProfileRepositoryInterface;
use Carbon\Carbon;
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

    public function update(int $userId, array $data): array
    {
        if (!empty($data['email'])) {
            $exists = DB::table('users')->where('email', $data['email'])->where('id', '!=', $userId)->exists();

            if ($exists) {
                throw ValidationException::withMessages(['email' => ['Email already exists.']]);
            }
        }

        if (!empty($data['national_id'])) {
            $exists = DB::table('user_profiles')->where('national_id', $data['national_id'])->where('user_id', '!=', $userId)->exists();

            if ($exists) {
                throw ValidationException::withMessages(['national_id' => ['National ID already exists.']]);
            }
        }

        $avatarPath = null;
        $oldAvatar = null;

        if (!empty($data['avatar'])) {
            $oldAvatar = DB::table('user_profiles')->where('user_id', $userId)->value('avatar');
            $avatarPath = $data['avatar']->store('avatars', 'public');
        }

        $userData = array_filter([
            'email' => $data['email'] ?? null,
        ], fn($v) => $v !== null);

        $profileData = array_filter([
            'full_name' => $data['name'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'bio' => $data['bio'] ?? null,
            'address' => $data['address'] ?? null,
            'avatar' => $avatarPath,
        ], fn($v) => $v !== null);

        try {
            $this->repository->updateProfile($userId, $userData, $profileData);
        } catch (\Throwable $e) {
            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            throw $e;
        }

        if ($avatarPath && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return $this->get($userId);
    }

    private function format(object $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->full_name,
            'company_name' => $p->company_name,
            'avatar' => $p->avatar ? Storage::url($p->avatar) : null,
            'account' => [
                'phone' => $p->phone,
                'email' => $p->email,
                'role' => $p->user_type,
                'created_at' => Carbon::parse($p->created_at)->toDateString(),
            ],
            'personal' => [
                'national_id' => $p->national_id,
                'date_of_birth' => $p->date_of_birth,
                'gender' => $p->gender,
                'bio' => $p->bio,
                'address' => $p->address,
            ],
        ];
    }
}