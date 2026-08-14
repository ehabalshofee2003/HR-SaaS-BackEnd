<?php

namespace App\Http\Controllers\Api\V1\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supervisor\UpdateAvatarRequest;
use App\Services\Supervisor\ProfileService;
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
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $profile = $this->profileService->get($userId);

        return response()->json(['success' => true, 'data' => $profile]);
    }

    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $profile = $this->profileService->updateAvatar($userId, $request->file('avatar'));

        return response()->json(['success' => true, 'data' => $profile]);
    }
}