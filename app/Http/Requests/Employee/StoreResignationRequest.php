<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreResignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supervisor_user_id' => 'required|exists:users,id',
            'reason' => 'required|string',
            'notice_date' => 'required|date',
            'last_working_date' => 'required|date|after_or_equal:notice_date',
        ];
    }
}