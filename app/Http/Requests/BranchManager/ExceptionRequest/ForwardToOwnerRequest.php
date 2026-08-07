<?php

namespace App\Http\Requests\BranchManager\ExceptionRequest;

use Illuminate\Foundation\Http\FormRequest;

class ForwardToOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => 'required|string|max:500',
        ];
    }
}