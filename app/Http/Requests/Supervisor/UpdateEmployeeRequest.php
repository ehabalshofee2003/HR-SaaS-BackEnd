<?php

namespace App\Http\Requests\Supervisor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('id'); // نأخذ ID الموظف من الراوت
        
        return [
            'full_name'     => 'sometimes|required|string|max:255',
            'phone'         => 'sometimes|required|string|regex:/^09[0-9]{8}$/|unique:users,phone,' . $userId,
            'email'         => 'sometimes|nullable|email|max:255|unique:users,email,' . $userId,
            'job_title'     => 'sometimes|required|string|max:255',
            'basic_salary'  => 'sometimes|required|numeric|min:0',
            'date_of_birth' => 'sometimes|nullable|date',
            'avatar'        => 'sometimes|nullable|image|max:2048',
            'documents'     => 'sometimes|nullable|array',
            'documents.*'   => 'sometimes|file|max:5120',
        ];
    }
}