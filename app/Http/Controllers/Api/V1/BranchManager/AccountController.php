<?php

namespace App\Http\Controllers\Api\V1\BranchManager;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchManager\Account\UpdateProfileRequest;
use App\Http\Requests\BranchManager\Account\ChangePasswordRequest;
use App\Http\Requests\BranchManager\Account\UpdateSettingsRequest;
use App\Http\Requests\BranchManager\Account\UpdateBranchDataRequest;
use App\Services\Identity\AccountService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class AccountController extends Controller
{
    public function __construct(
        protected AccountService $accountService
    ) {}

    public function profile(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        return response()->json(['data' => $this->accountService->getProfile($user)]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $profile = $this->accountService->updateProfile($user, $request->validated(), $request->file('avatar'));

        return response()->json(['data' => $profile]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $this->accountService->changePassword($user, $request->validated()['current_password'], $request->validated()['new_password']);

        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح.']);
    }

    public function settings(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        return response()->json(['data' => $this->accountService->getSettings($user)]);
    }

    public function updateSettings(UpdateSettingsRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $settings = $this->accountService->updateSettings($user, $request->validated());

        return response()->json(['data' => $settings]);
    }

    public function branchData(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        return response()->json(['data' => $this->accountService->getBranchData($user)]);
    }

    public function updateBranchData(UpdateBranchDataRequest $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $branch = $this->accountService->updateBranchData($user, $request->validated());

        return response()->json(['data' => $branch]);
    }
}