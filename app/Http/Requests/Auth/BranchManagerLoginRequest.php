<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class BranchManagerLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone' => 'required|string|regex:/^09[0-9]{8}$/',
            'password' => 'required|string|min:8',
            'remember_me' => 'nullable|boolean',
        ];
    }
}