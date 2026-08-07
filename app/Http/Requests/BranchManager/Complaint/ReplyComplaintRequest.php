<?php

namespace App\Http\Requests\BranchManager\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class ReplyComplaintRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['message' => 'required|string|max:2000'];
    }
}