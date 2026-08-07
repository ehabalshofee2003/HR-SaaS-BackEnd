<?php

namespace App\Http\Requests\BranchManager\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
            'department_id' => 'required|integer|exists:departments,id',
            'job_title' => 'required|string|max:255',
            'basic_salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
            'contract_type' => 'required|in:full_time,part_time,contract',
            'national_id' => 'nullable|string|unique:user_profiles,national_id',
            'date_of_birth' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|max:2048',
            'documents' => 'nullable|array',
            'documents.*.file' => 'required_with:documents|file|max:5120',
            'documents.*.type' => 'required_with:documents|string|max:100',
        ];
    }
}