<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exception_type_id' => 'required|exists:exception_types,id',
            'request_date'      => 'required|date',
            'start_time'        => 'nullable|date_format:H:i',
            'end_time'          => 'nullable|date_format:H:i',
            'duration_minutes'  => 'required|integer|min:0',
            'reason'            => 'required|string',
            'attachment'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }
}