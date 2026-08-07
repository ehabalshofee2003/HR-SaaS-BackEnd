<?php

namespace App\Http\Requests\BranchManager\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreGeneralTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'department_id' => 'nullable|integer|exists:departments,id',
            'employee_user_id' => 'required|integer|exists:users,id',
            'due_date' => 'required|date',
            'priority' => 'nullable|in:high,medium,low',
        ];
    }
}