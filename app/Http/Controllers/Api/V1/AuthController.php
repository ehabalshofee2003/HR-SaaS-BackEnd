<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Identity\AuthService;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $result = $this->authService->login($data['account'], $data['password'], $data['remember_me'] ?? false);

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح.',
            'token' => $result['token'],
            'data' => $result['user'],
        ]);
    }

    public function logout(Request $request)
    {
        $user = User::find(Auth::id());
        if (!$user) throw new Exception('غير مصرح.', 401);

        $this->authService->logout($user);

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح.']);
    }
}