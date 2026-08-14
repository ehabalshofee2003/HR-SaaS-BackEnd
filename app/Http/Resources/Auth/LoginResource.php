<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];

        return [
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $this->resource['token'],
            'token_type' => 'Bearer',
            'data' => [
                'id' => $user->id,
                'user_type' => $user->user_type,
                'phone' => $user->phone,
                'email' => $user->email,
                'status' => $user->status,
                'full_name' => $user->profile?->full_name,
                'avatar' => $user->profile?->avatar,
                'branch_id' => $user->branch_id,
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ];
    }
}