<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\Form\Request;
use Illuminate\Foundation\Http\FormRequest;

class CompleteTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'notes' => 'sometimes|string|nullable',
            'attachments' => 'sometimes|array|max:5',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,docx|max:2048'
        ];
    }
}