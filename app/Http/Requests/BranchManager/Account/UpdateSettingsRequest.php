<?php

namespace App\Http\Requests\BranchManager\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'work_start_time' => 'sometimes|date_format:H:i',
            'work_end_time' => 'sometimes|date_format:H:i',
            'late_deduction_percent' => 'sometimes|numeric|min:0|max:100',
            'absence_deduction_full_day' => 'sometimes|boolean',
            'notify_new_employee' => 'sometimes|boolean',
            'notify_leave_request' => 'sometimes|boolean',
            'notify_complaint' => 'sometimes|boolean',
            'notify_resignation' => 'sometimes|boolean',
        ];
    }
}