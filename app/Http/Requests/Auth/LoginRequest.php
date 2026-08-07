<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'account' => 'required|string', // يقبل بريد إلكتروني أو رقم هاتف
            'password' => 'required|string|min:6',
            'remember_me' => 'nullable|boolean',
        ];
    }
}