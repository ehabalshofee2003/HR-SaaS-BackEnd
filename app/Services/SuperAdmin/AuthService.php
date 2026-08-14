<?php

namespace App\Services\SuperAdmin;

use App\Models\Identity\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)
            ->where('user_type', 'super_admin')
            ->whereNull('deleted_at')
            ->first();

        if (!$user || !Hash::check($password, $user->password_hash)) {
            return ['success' => false, 'code' => 401, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'];
        }

        if ($user->status === 'suspended') {
            return ['success' => false, 'code' => 403, 'message' => 'الحساب موقوف.'];
        }

        $token = $user->createToken('super_admin_token')->plainTextToken;
        $user->update(['last_login_at' => now()]);
        $user->load('profile');

        return [
            'success' => true,
            'code' => 200,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->profile?->full_name,
                'email' => $user->email,
            ],
        ];
    }
}