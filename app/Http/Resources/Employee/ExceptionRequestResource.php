<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ExceptionRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'exception_type'    => $this->whenLoaded('exceptionType', function () {
                return [
                    'id'   => $this->exceptionType->id,
                    'name' => $this->exceptionType->name,
                ];
            }),
            'request_date'      => Carbon::parse($this->request_date)->format('Y-m-d'),
            'start_time'        => $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : null,
            'end_time'          => $this->end_time ? Carbon::parse($this->end_time)->format('H:i') : null,
            'duration_minutes'  => $this->duration_minutes,
            'reason'            => $this->reason,
            'attachment'        => $this->attachment ? Storage::url($this->attachment) : null,
            'status'            => $this->status,
            'rejection_reason'  => $this->when($this->status === 'rejected', $this->rejection_reason),
            'approved_at'       => $this->approved_at ? Carbon::parse($this->approved_at)->format('Y-m-d H:i:s') : null,
            'created_at'        => Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}