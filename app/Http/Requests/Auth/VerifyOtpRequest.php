<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
public function rules(): array
{
    return [
        'phone' => ['required', 'string', 'max:50'],
        'otp' => ['required', 'string', 'size:6'],
        'device_id' => ['nullable', 'string', 'max:255'],
        'device_name' => ['nullable', 'string', 'max:255'],
    ];
}
}