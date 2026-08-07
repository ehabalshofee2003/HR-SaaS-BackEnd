<?php

namespace App\Http\Requests\BranchManager\ExceptionRequest;

use Illuminate\Foundation\Http\FormRequest;

class RejectExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => 'required|string|max:500',
        ];
    }
}