<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // المسار محمي بـ sanctum، لذلك المصرح له هو true
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'avatar' => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'national_id' => ['sometimes', 'string', 'max:50'],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
        ];
    }
}