<?php

namespace App\Http\Requests\BranchManager\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'full_name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|regex:/^09[0-9]{8}$/|unique:users,phone,' . $id,
            'email' => 'sometimes|nullable|email|unique:users,email,' . $id,
            'department_id' => 'sometimes|required|integer|exists:departments,id',
            'supervisor_id' => 'sometimes|nullable|integer|exists:users,id',
            'job_title' => 'sometimes|required|string|max:255',
            'basic_salary' => 'sometimes|required|numeric|min:0',
            'hire_date' => 'sometimes|required|date',
            'contract_type' => 'sometimes|required|in:full_time,part_time,contract',
            'national_id' => 'sometimes|nullable|string|unique:user_profiles,national_id,' . $id . ',user_id',
            'date_of_birth' => 'sometimes|nullable|date|before:today',
            'address' => 'sometimes|nullable|string|max:500',
            'avatar' => 'sometimes|nullable|image|max:2048',
        ];
    }
}