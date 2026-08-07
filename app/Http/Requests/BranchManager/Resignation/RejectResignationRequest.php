<?php

namespace App\Http\Requests\BranchManager\Resignation;

use Illuminate\Foundation\Http\FormRequest;

class RejectResignationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['reason' => 'required|string|max:500'];
    }
}