<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'leave_type'    => $this->whenLoaded('leaveType', fn() => [
                'id'   => $this->leaveType->id,
                'name' => $this->leaveType->name,
            ]),
            'start_date'    => $this->start_date?->format('Y-m-d'),
            'end_date'      => $this->end_date?->format('Y-m-d'),
            'status'        => $this->status,
            'reason'        => $this->reason,
            'attachment_url'=> $this->attachment ? Storage::url($this->attachment) : null,
            'approver'      => $this->whenLoaded('approver', fn() => [
                'id'   => $this->approver->id,
                'name' => $this->approver->full_name, // افتراض وجود Accessor
            ]),
            'rejection_reason' => $this->when($this->status === 'rejected', $this->rejection_reason),
            'created_at'    => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}