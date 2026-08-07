<?php

namespace App\Http\Requests\BranchManager\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'full_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|nullable|email|unique:users,email,' . $this->user()->id,
            'avatar' => 'sometimes|nullable|image|max:2048',
        ];
    }
}