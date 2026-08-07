<?php

namespace App\Http\Requests\BranchManager\Supervisor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupervisorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|regex:/^09[0-9]{8}$/|unique:users,phone,' . $this->route('id'),
            'email' => 'sometimes|nullable|email|unique:users,email,' . $this->route('id'),
            'department_id' => 'sometimes|nullable|integer|exists:departments,id',
        ];
    }
}