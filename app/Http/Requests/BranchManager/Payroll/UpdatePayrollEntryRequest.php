<?php

namespace App\Http\Requests\BranchManager\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'gross_salary' => 'sometimes|required|numeric|min:0',
            'total_deductions' => 'sometimes|required|numeric|min:0',
            'total_bonuses' => 'sometimes|required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ];
    }
}