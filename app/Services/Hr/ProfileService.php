<?php

namespace App\Services\Hr;

use App\Repositories\Identity\UserProfileRepository;
use App\Models\Identity\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ProfileService
{
    public function __construct(
        private UserProfileRepository $profileRepository
    ) {}

    public function getProfile(User $user): User
    {
        return $this->profileRepository->getUserWithProfile($user->id);
    }

    public function updateProfile(User $user, array $validated, $avatar = null): User
    {
        $newAvatarPath = null;

        if ($avatar) {
            if ($user->profile && $user->profile->avatar) {
                Storage::disk('public')->delete($user->profile->avatar);
            }
            $newAvatarPath = $avatar->store('avatars/' . $user->id, 'public');
        }

        DB::beginTransaction();
        try {
            $profileData = [];

            if (isset($validated['full_name'])) {
                $profileData['full_name'] = $validated['full_name'];
            }
            if (isset($validated['national_id'])) {
                $profileData['national_id'] = $validated['national_id'];
            }
            if (isset($validated['date_of_birth'])) {
                $profileData['date_of_birth'] = $validated['date_of_birth'];
            }
            if ($newAvatarPath) {
                $profileData['avatar'] = $newAvatarPath;
            }

            if (!empty($profileData)) {
                // updateOrCreate بدل update() — يضمن إنشاء السجل إن لم يكن موجوداً أصلاً
                $user->profile()->updateOrCreate(['user_id' => $user->id], $profileData);
            }

            DB::commit();
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            if ($newAvatarPath) {
                Storage::disk('public')->delete($newAvatarPath);
            }

            if ($e->errorInfo[1] == 1062) {
                throw new Exception('رقم الهوية الوطنية مستخدم مسبقاً بحساب آخر.');
            }

            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            if ($newAvatarPath) {
                Storage::disk('public')->delete($newAvatarPath);
            }

            throw $e;
        }

        $user->load(['profile', 'employeeDetail']);

        return $user;
    }

    public function changePassword(array $data)
    {
        $user = $this->getAuthenticatedUser();

        if (!Hash::check($data['old_password'], $user->password_hash)) {
            return ['success' => false, 'message' => 'Old password is incorrect.', 'code' => 400];
        }

        $user->update(['password_hash' => Hash::make($data['new_password'])]);
        return ['success' => true, 'message' => 'Password changed successfully.', 'code' => 200];
    }

    public function changePhone(array $data)
    {
        $user = $this->getAuthenticatedUser();

        $exists = User::where('phone', $data['phone'])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($exists) {
            return ['success' => false, 'message' => 'رقم الهاتف مستخدم مسبقاً بحساب آخر.', 'code' => 422];
        }

        $user->update(['phone' => $data['phone']]);
        return ['success' => true, 'message' => 'Phone changed successfully.', 'code' => 200];
    }

    private function getAuthenticatedUser(): User
    {
        $user = User::find(Auth::id());
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        return $user;
    }

    public function logout(Request $request)
    {
        $user = $this->getAuthenticatedUser();

        $tokenId = explode('|', $request->bearerToken() ?? '')[0];

        if ($tokenId) {
            $user->tokens()->where('id', $tokenId)->delete();
        }

        return [
            'success' => true,
            'message' => 'Logged out successfully.',
            'code' => 200
        ];
    }
}