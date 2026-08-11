<?php

namespace App\Http\Requests\BranchManager\Workshop;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkshopRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'location' => 'nullable|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'capacity' => 'required|integer|min:0',
        ];
    }
}