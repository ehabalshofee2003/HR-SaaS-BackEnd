<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject'       => 'required|string|max:255',
            'description'   => 'required|string|max:10000',
            'department_id' => 'sometimes|nullable|exists:departments,id',
            'is_anonymous'  => 'sometimes|boolean',
        ];
    }
}