<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreExceptionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'exception_type_id' => 'required|integer|exists:exception_types,id',
            'reason' => 'required|string|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }
}