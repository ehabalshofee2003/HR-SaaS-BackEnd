<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type, // daily أو ad_hoc
            'status' => $this->status, // pending, in_progress, etc.
            'due_date' => $this->due_date?->format('Y-m-d H:i'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i'),
            'reward_amount' => $this->reward_amount,
            
            // جلب اسم المشرف الذي أصدر المهمة
            'supervisor_name' => $this->whenLoaded('supervisor', fn() => $this->supervisor->profile?->full_name ?? 'N/A'),
        ];
    }
}