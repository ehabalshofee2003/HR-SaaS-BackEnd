<?php

namespace App\Http\Requests\BranchManager\Announcement;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'target' => 'required|in:all,department,employee',
            'target_department_id' => 'required_if:target,department|integer|exists:departments,id',
            'target_employee_id' => 'required_if:target,employee|integer|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'attachments' => 'nullable|array|max:3',
            'attachments.*' => 'file|max:5120',
        ];
    }
}