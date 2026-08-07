<?php

namespace App\Services\Identity;

use App\Models\Identity\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Exception;

class AuthService
{
    public function login(string $account, string $password, bool $rememberMe = false): array
    {
        $user = DB::table('users')
            ->where('phone', $account)
            ->orWhere('email', $account)
            ->whereNull('deleted_at')
            ->first();

        if (!$user || !Hash::check($password, $user->password_hash)) {
            throw new Exception('بيانات الدخول غير صحيحة.', 401);
        }

        if ($user->status !== 'active') {
            throw new Exception('حسابك معلق أو غير نشط.', 403);
        }

        // فحص خاص بمدير الفرع فقط — لا يؤثر على أي دور آخر
        if ($user->user_type === 'manager' && $user->branch_id) {
            $branch = DB::table('branches')->where('id', $user->branch_id)->first();
            if ($branch && $branch->status !== 'active') {
                throw new Exception('فرعك معلق. تواصل مع المالك.', 403);
            }
        }

        DB::table('users')->where('id', $user->id)->update(['last_login_at' => Carbon::now()]);

        $userModel = User::find($user->id);
        $expiresAt = $rememberMe ? Carbon::now()->addDays(30) : null;
        $tokenName = $user->user_type . '-app-token';
        $token = $userModel->createToken($tokenName, ['*'], $expiresAt)->plainTextToken;

        DB::table('audit_logs')->insert([
            'user_id' => $user->id,
            'company_id' => $userModel->getCurrentCompanyId(),
            'action' => 'login',
            'entity_type' => 'users',
            'entity_id' => $user->id,
            'created_at' => Carbon::now(),
        ]);

        $profile = DB::table('user_profiles')->where('user_id', $user->id)->first();

        return [
            'user' => (object) [
                'id' => $user->id,
                'user_type' => $user->user_type,
                'phone' => $user->phone,
                'email' => $user->email,
                'status' => $user->status,
                'full_name' => $profile->full_name ?? null,
                'avatar' => $profile->avatar ?? null,
                'branch_id' => $user->branch_id ?? null,
            ],
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();

        DB::table('audit_logs')->insert([
            'user_id' => $user->id,
            'company_id' => $user->getCurrentCompanyId(),
            'action' => 'logout',
            'entity_type' => 'users',
            'entity_id' => $user->id,
            'created_at' => Carbon::now(),
        ]);
    }
}