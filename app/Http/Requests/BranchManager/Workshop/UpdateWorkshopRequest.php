<?php

namespace App\Http\Requests\BranchManager\Workshop;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkshopRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string|max:5000',
            'location' => 'sometimes|nullable|string|max:500',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'capacity' => 'sometimes|required|integer|min:0',
        ];
    }
}