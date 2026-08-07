<?php

namespace App\Http\Requests\BranchManager\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class AddPayrollExceptionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'adjustment_type' => 'required|in:bonus,deduction,correction',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ];
    }
}