<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateProfileRequest;
use App\Services\Owner\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService,
    ) {}

    public function show(): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $profile = $this->profileService->get($userId);

        return response()->json(['success' => true, 'data' => $profile]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 401);
        }

        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar');
        }

        $profile = $this->profileService->update($userId, $data);

        return response()->json(['success' => true, 'data' => $profile]);
    }
}