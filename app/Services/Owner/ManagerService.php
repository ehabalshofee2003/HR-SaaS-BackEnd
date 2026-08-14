<?php

namespace App\Services\Owner;

use App\Repositories\Interfaces\Owner\ManagerRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManagerService
{
    public function __construct(
        private ManagerRepositoryInterface $repository,
    ) {}

    public function list(int $companyId, array $filters = []): array
    {
        return array_map(fn($row) => $this->format($row), $this->repository->list($companyId, $filters));
    }

    public function create(int $companyId, array $data): array
    {
        if ($this->repository->phoneExists($data['phone'])) {
            throw ValidationException::withMessages(['phone' => ['Phone number already exists.']]);
        }

        if (!empty($data['email']) && $this->repository->emailExists($data['email'])) {
            throw ValidationException::withMessages(['email' => ['Email already exists.']]);
        }

        if (!empty($data['branch_id'])) {
            if (!$this->repository->branchBelongsToCompany($data['branch_id'], $companyId)) {
                throw ValidationException::withMessages(['branch_id' => ['Branch not found.']]);
            }

            if ($this->repository->branchHasManager($data['branch_id'])) {
                throw ValidationException::withMessages(['branch_id' => ['This branch already has a manager assigned.']]);
            }
        }

        // رفع الصورة خارج الـ Transaction
        $avatarPath = null;
        if (!empty($data['avatar'])) {
            $avatarPath = $data['avatar']->store('avatars', 'public');
        }

        try {
            $managerId = DB::transaction(function () use ($companyId, $data, $avatarPath) {
                $userId = $this->repository->createUser([
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'password_hash' => Hash::make(Str::random(32)),
                    'company_id' => $companyId,
                    'branch_id' => $data['branch_id'] ?? null,
                ]);

                $this->repository->createProfile([
                    'user_id' => $userId,
                    'full_name' => $data['name'],
                    'avatar' => $avatarPath,
                    'national_id' => $data['national_id'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                ]);

                return $userId;
            });
        } catch (\Throwable $e) {
            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            throw $e;
        }

        return $this->format($this->repository->find($managerId, $companyId));
    }

    public function get(int $id, int $companyId): array
    {
        $manager = $this->repository->find($id, $companyId);

        if (!$manager) {
            throw ValidationException::withMessages(['manager' => ['Manager not found.']]);
        }

        return $this->format($manager);
    }

    public function update(int $id, int $companyId, array $data): array
    {
        $existing = $this->repository->find($id, $companyId);

        if (!$existing) {
            throw ValidationException::withMessages(['manager' => ['Manager not found.']]);
        }

        if (!empty($data['phone']) && $data['phone'] !== $existing->phone) {
            if ($this->repository->phoneExists($data['phone'], $id)) {
                throw ValidationException::withMessages(['phone' => ['Phone number already exists.']]);
            }
        }

        if (!empty($data['email']) && $data['email'] !== $existing->email) {
            if ($this->repository->emailExists($data['email'], $id)) {
                throw ValidationException::withMessages(['email' => ['Email already exists.']]);
            }
        }

        if (array_key_exists('branch_id', $data) && $data['branch_id'] !== null) {
            if (!$this->repository->branchBelongsToCompany($data['branch_id'], $companyId)) {
                throw ValidationException::withMessages(['branch_id' => ['Branch not found.']]);
            }

            if ($this->repository->branchHasManager($data['branch_id'], $id)) {
                throw ValidationException::withMessages(['branch_id' => ['This branch already has a manager assigned.']]);
            }
        }

        $avatarPath = null;
        $oldAvatar = null;

        if (!empty($data['avatar'])) {
            $oldAvatar = DB::table('user_profiles')->where('user_id', $id)->value('avatar');
            $avatarPath = $data['avatar']->store('avatars', 'public');
        }

        try {
            DB::transaction(function () use ($id, $data, $avatarPath) {
                $userData = array_filter([
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'branch_id' => array_key_exists('branch_id', $data) ? $data['branch_id'] : null,
                ], fn($v) => $v !== null);

                $this->repository->updateUser($id, $userData);

                $profileData = array_filter([
                    'full_name' => $data['name'] ?? null,
                    'national_id' => $data['national_id'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'avatar' => $avatarPath,
                ], fn($v) => $v !== null);

                $this->repository->updateProfile($id, $profileData);
            });
        } catch (\Throwable $e) {
            if ($avatarPath) {
                Storage::disk('public')->delete($avatarPath);
            }
            throw $e;
        }

        if ($avatarPath && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return $this->format($this->repository->find($id, $companyId));
    }

    public function delete(int $id, int $companyId): array
    {
        $manager = $this->repository->find($id, $companyId);

        if (!$manager) {
            throw ValidationException::withMessages(['manager' => ['Manager not found.']]);
        }

        $this->repository->softDelete($id);

        return ['success' => true, 'message' => 'Manager deleted successfully.'];
    }

    private function format(object $manager): array
    {
        return [
            'id' => $manager->id,
            'name' => $manager->name,
            'phone' => $manager->phone,
            'email' => $manager->email,
            'avatar' => $manager->avatar ? Storage::url($manager->avatar) : null,
            'status' => $manager->status,
            'branch' => $manager->branch_id ? [
                'id' => $manager->branch_id,
                'name' => $manager->branch_name,
            ] : null,
            'created_at' => \Carbon\Carbon::parse($manager->created_at)->toIso8601String(),
        ];
    }
}