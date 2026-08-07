<?php

namespace App\Http\Requests\Supervisor;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'full_name'     => 'required|string|max:255',
            'phone'         => 'required|string|regex:/^09[0-9]{8}$/|unique:users,phone',
            'email'         => 'nullable|email|max:255|unique:users,email',
            'password'      => 'required|string|min:8', // الفرونت يرسل الباسورد المؤقت
            'job_title'     => 'required|string|max:255',
            'basic_salary'  => 'required|numeric|min:0',
            'hire_date'     => 'required|date',
            'contract_type' => 'required|in:full_time,part_time,contract',
            'national_id'   => 'nullable|string|unique:user_profiles,national_id',
            'date_of_birth' => 'nullable|date',
            'avatar'        => 'nullable|image|max:2048',
            'documents'     => 'nullable|array',
            'documents.*'   => 'file|max:5120', // ملفات pdf, صور إلخ
        ];
    }
}