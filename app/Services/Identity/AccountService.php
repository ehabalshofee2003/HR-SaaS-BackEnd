<?php

namespace App\Services\Identity;

use App\Models\Identity\User;
use App\Repositories\Identity\AccountRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Exception;

class AccountService
{
    public function __construct(
        protected AccountRepository $accountRepository
    ) {}

    public function getProfile(User $user): object
    {
        return DB::table('users')
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->where('users.id', $user->id)
            ->select('users.id', 'users.phone', 'users.email', 'users.status', 'user_profiles.full_name', 'user_profiles.avatar')
            ->first();
    }

    public function updateProfile(User $user, array $data, ?UploadedFile $avatar = null): object
    {
        $avatarPath = null;

        try {
            if ($avatar) {
                $avatarPath = $avatar->store('managers/avatars', 'public');
            }

            if (!empty($data['email'])) {
                DB::table('users')->where('id', $user->id)->update(['email' => $data['email'], 'updated_at' => now()]);
            }

            $profileData = [];
            if (!empty($data['full_name'])) $profileData['full_name'] = $data['full_name'];
            if ($avatarPath) $profileData['avatar'] = $avatarPath;

            if (!empty($profileData)) {
                $profileData['updated_at'] = now();
                DB::table('user_profiles')->where('user_id', $user->id)->update($profileData);
            }
        } catch (Exception $e) {
            if ($avatarPath) Storage::disk('public')->delete($avatarPath);
            throw $e;
        }

        return $this->getProfile($user);
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password_hash)) {
            throw new Exception('كلمة المرور الحالية غير صحيحة.');
        }

        DB::table('users')->where('id', $user->id)->update([
            'password_hash' => Hash::make($newPassword),
            'updated_at' => now(),
        ]);
    }

    public function getSettings(User $user): array
    {
        return $this->accountRepository->getSettings($user->getCurrentCompanyId());
    }

    public function updateSettings(User $user, array $data): array
    {
        $companyId = $user->getCurrentCompanyId();

        foreach ($data as $key => $value) {
            $this->accountRepository->upsertSetting($companyId, $key, (string) $value);
        }

        return $this->accountRepository->getSettings($companyId);
    }

    public function getBranchData(User $user): object
    {
        $branch = $this->accountRepository->findBranch($user->getCurrentBranchId());

        if (!$branch) {
            throw new Exception('الفرع غير موجود.', 404);
        }

        return $branch;
    }

    public function updateBranchData(User $user, array $data): object
    {
        $branchId = $user->getCurrentBranchId();
        $branch = $this->accountRepository->findBranch($branchId);

        if (!$branch) {
            throw new Exception('الفرع غير موجود.', 404);
        }

        $this->accountRepository->updateBranch($branchId, $data);

        return $this->accountRepository->findBranch($branchId);
    }
}