<?php

namespace App\Http\Requests\BranchManager\Supervisor;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupervisorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^09[0-9]{8}$/|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'department_id' => 'nullable|integer|exists:departments,id',
        ];
    }
}