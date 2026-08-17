<?php

namespace App\Http\Requests\Supervisor\Employee;

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
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'job_title' => ['required', 'string', 'max:255'],
            'contract_type' => ['required', 'in:full_time,part_time,contract,temporary'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'hire_date' => ['required', 'date'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}