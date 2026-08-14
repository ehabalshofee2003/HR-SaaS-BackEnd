<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPhoneChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_phone' => ['required', 'string', 'max:50'],
            'otp' => ['required', 'string', 'size:6'],
        ];
    }
}