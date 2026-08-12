<?php

namespace App\Http\Requests\BranchManager\Report;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'department_id' => 'nullable|integer|exists:departments,id',
            'employee_id' => 'nullable|integer|exists:users,id',
        ];
    }
}