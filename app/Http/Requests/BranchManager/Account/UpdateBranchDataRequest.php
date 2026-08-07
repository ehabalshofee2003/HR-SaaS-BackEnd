<?php

namespace App\Http\Requests\BranchManager\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchDataRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'description' => 'sometimes|nullable|string|max:2000',
        ];
    }
}