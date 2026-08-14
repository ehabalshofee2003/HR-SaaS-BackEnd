<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class ManagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'phone' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'date_format:Y-m-d'],
            'gender' => ['nullable', 'string', 'in:male,female'],
        ];
    }
}