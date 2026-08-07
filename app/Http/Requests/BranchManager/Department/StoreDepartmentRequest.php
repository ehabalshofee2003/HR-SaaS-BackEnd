<?php

namespace App\Http\Requests\BranchManager\Department;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',

            // إما اختيار مشرف موجود، أو إنشاء مشرف جديد
            'supervisor_user_id' => 'nullable|integer|exists:users,id|required_without:new_supervisor',
            'new_supervisor' => 'nullable|array|required_without:supervisor_user_id',
            'new_supervisor.full_name' => 'required_with:new_supervisor|string|max:255',
            'new_supervisor.phone' => 'required_with:new_supervisor|string|regex:/^09[0-9]{8}$/|unique:users,phone',
            'new_supervisor.email' => 'nullable|email|unique:users,email',
        ];
    }
}