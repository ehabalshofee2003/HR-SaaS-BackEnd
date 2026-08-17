<?php

namespace App\Http\Requests\Supervisor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['sometimes', 'integer'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'due_date' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'due_time' => ['sometimes', 'date_format:H:i'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'status' => ['sometimes', 'string', 'in:pending,in_progress,completed,cancelled'],
        ];
    }
}