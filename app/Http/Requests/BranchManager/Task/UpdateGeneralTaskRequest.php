<?php

namespace App\Http\Requests\BranchManager\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:5000',
            'employee_user_id' => 'sometimes|required|integer|exists:users,id',
            'due_date' => 'sometimes|required|date',
            'priority' => 'sometimes|nullable|in:high,medium,low',
        ];
    }
}