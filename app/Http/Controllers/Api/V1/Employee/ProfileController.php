<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\UpdateProfileRequest;
use App\Http\Resources\Employee\ProfileResource;
use App\Models\Identity\User; // <-- إضافة الاستدعاء
use App\Services\Hr\ProfileService;
use Exception;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    /**
     * GET /api/v1/employees/profile
     */
     
    public function show(): ProfileResource
{
    $user = User::find(Auth::id());
    if (!$user) {
        abort(401, 'Unauthorized');
    }

    // هذا السطر هو السحر! يخبر لارافيل يجلب بيانات البروفايل وتفاصيل الموظف
    $user->load(['profile', 'employeeDetail']); 

    return new ProfileResource($user);
}

    /**
     * PUT /api/v1/employees/profile
     */
    public function update(UpdateProfileRequest $request)
    {
        try {
            // جلب المستخدم بشكل صريح لحل مشكلة Intelephense
            $user = User::find(Auth::id());
            

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            $validated = $request->validated();
 
            $avatar = $request->hasFile('avatar') ? $request->file('avatar') : null;

            $updatedUser = $this->profileService->updateProfile($user, $validated, $avatar);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => new ProfileResource($updatedUser)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile. Please check your connection.',
            ], 500);
        }
    }
}