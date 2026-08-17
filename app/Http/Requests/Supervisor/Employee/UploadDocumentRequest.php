<?php

namespace App\Http\Requests\Supervisor\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:5120'],
            'type' => ['required', 'string', 'max:100'],
        ];
    }
}