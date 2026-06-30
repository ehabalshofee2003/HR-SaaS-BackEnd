<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class WorkshopListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // نستخرج قائمة الـ IDs التي سجل فيها المستخدم من الـ additional (لمنع N+1 Query)
        $registeredIds = $this->additional['registered_ids'] ?? [];
        
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'location'   => $this->location,
            'start_date' => Carbon::parse($this->start_date)->format('Y-m-d H:i'),
            'end_date'   => Carbon::parse($this->end_date)->format('Y-m-d H:i'),
            'status'     => $this->status,
            'capacity'   => $this->capacity,
            // حق ديناميكي لمعرفة هل المستخدم مسجل فيها أم لا
            'is_registered' => in_array($this->id, $registeredIds),
        ];
    }
}