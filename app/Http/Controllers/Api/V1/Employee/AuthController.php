<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\LoginRequest;
use App\Http\Resources\Employee\ProfileResource;
use App\Models\Identity\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /api/v1/employees/login
     */
    public function login(LoginRequest $request)
    {
        $account = $request->input('account');
        
        // البحث عن المستخدم بالإيميل أو الهاتف
        $user = User::where('email', $account)
                    ->orWhere('phone', $account)
                    ->first();

        // التحقق من وجود المستخدم وصحة كلمة المرور
        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        // التحقق من أن الحساب نشط
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is suspended or inactive.'
            ], 403);
        }

        // تحديث وقت آخر تسجيل دخول
        $user->update(['last_login_at' => now()]);
        $user->load(['profile', 'employeeDetail']);

        // إنشاء التوكن (Sanctum)
        $token = $user->createToken('employee-app-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'data' => new ProfileResource($user)
        ]);
    }
    
}