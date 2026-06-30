<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class WorkshopDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'location'     => $this->location,
            'start_date'   => Carbon::parse($this->start_date)->format('Y-m-d H:i'),
            'end_date'     => Carbon::parse($this->end_date)->format('Y-m-d H:i'),
            'status'       => $this->status,
            'capacity'     => $this->capacity,
            'is_registered' => $this->whenLoaded('attendees', function () {
                // التحقق مما إذا كان المستخدم الحالي مسجل في ورشة التفاصيل هذه
                $userId = \Illuminate\Support\Facades\Auth::id();
                return $this->attendees->contains('employee_user_id', $userId);
            }, false),
            'created_by'   => $this->whenLoaded('creator', fn() => $this->creator->userProfile?->full_name),
            'created_at'   => Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}