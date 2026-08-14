<?php

namespace App\Http\Requests\Supervisor;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['required', 'date', 'date_format:Y-m-d'],
            'due_time' => ['required', 'date_format:H:i'],
            'employee_id' => ['required', 'integer'],
            'priority' => ['required', 'string', 'in:low,medium,high'],
        ];
    }
}