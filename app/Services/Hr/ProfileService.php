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

    /**
     * جلب البروفايل (الدالة المفقودة)
     */
    public function getProfile(User $user): User
    {
        return $this->profileRepository->getUserWithProfile($user->id);
    }

    /**
     * تحديث البروفايل
     */
public function updateProfile(User $user, array $validated, $avatar = null): User
{
    $newAvatarPath = null;

    // 1. رفع الملف خارج الـ Transaction (Resilience Pattern)
    if ($avatar) {
        // حذف الصورة القديمة إذا وجدت لتوفير المساحة
        if ($user->profile && $user->profile->avatar) {
            Storage::disk('public')->delete($user->profile->avatar);
        }
        $newAvatarPath = $avatar->store('avatars/' . $user->id, 'public');
    }

    // 2. بدء Transaction لقاعدة البيانات
    DB::beginTransaction();
    try {
        $profileData = [];
        
        // نحدّث فقط الحقول التي تم إرسالها فعلياً (بسبب استخدام sometimes في الـ Request)
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

        // التأكد من أن هناك بيانات فعلية للتحديث قبل استدعاء قاعدة البيانات
        if (!empty($profileData)) {
            $user->profile()->update($profileData);
        }

        DB::commit();
    } catch (\Exception $e) {
        // ... باقي كود الـ catch
    } catch (\Exception $e) {
        DB::rollBack();

        // Compensating Action: حذف الصورة الجديدة إذا فشلت قاعدة البيانات
        if ($newAvatarPath) {
            Storage::disk('public')->delete($newAvatarPath);
        }

        throw $e;
    }

    // إعادة تحميل العلاقات لإرجاع البيانات المحدثة في الـ Response
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
        
        // استخراج ID التوكن الحالي من الـ Bearer Token بشكل آمن
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