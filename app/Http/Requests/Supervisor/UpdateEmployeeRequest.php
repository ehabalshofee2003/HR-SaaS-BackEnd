<?php

namespace App\Http\Requests\Supervisor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_title' => ['sometimes', 'string', 'max:255'],
            'employment_status' => ['sometimes', 'string', 'in:active,probation,terminated,resigned'],
        ];
    }
}