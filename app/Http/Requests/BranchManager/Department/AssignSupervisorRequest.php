<?php

namespace App\Http\Requests\BranchManager\Department;

use Illuminate\Foundation\Http\FormRequest;

class AssignSupervisorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supervisor_user_id' => 'required|integer|exists:users,id',
        ];
    }
}