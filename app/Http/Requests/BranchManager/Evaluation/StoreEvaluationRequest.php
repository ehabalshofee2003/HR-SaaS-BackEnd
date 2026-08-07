<?php

namespace App\Http\Requests\BranchManager\Evaluation;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_user_id' => 'required|integer|exists:users,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'notes' => 'nullable|string|max:1000',
            'scores' => 'required|array|min:1',
            'scores.*.criteria_id' => 'required|integer|exists:evaluation_criteria,id',
            'scores.*.score' => 'required|numeric|min:1|max:5',
            'scores.*.comments' => 'nullable|string|max:500',
        ];
    }
}