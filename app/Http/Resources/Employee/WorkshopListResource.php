<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class WorkshopListResource extends JsonResource
{
public function toArray(Request $request): array
{
    $registeredIds = $this->additional['registered_ids'] ?? [];
    $registeredCounts = $this->additional['registered_counts'] ?? [];

    return [
        'id'         => $this->id,
        'title'      => $this->title,
        'instructor' => $this->instructor,
        'location'   => $this->location,
        'start_date' => Carbon::parse($this->start_date)->format('Y-m-d H:i'),
        'end_date'   => Carbon::parse($this->end_date)->format('Y-m-d H:i'),
        'status'     => $this->status,
        'capacity'   => $this->capacity,
        'registered_count' => $registeredCounts[$this->id] ?? 0,
        'is_registered' => in_array($this->id, $registeredIds),
    ];
}
}