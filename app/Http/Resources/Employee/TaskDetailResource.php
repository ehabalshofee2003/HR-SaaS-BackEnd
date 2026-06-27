<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class TaskDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'description'   => $this->description,
            'type'          => $this->type,
            'priority'      => $this->priority,
            'status'        => $this->status,
            'start_date'    => Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
            'due_date'      => Carbon::parse($this->due_date)->format('Y-m-d H:i:s'),
            'completed_at'  => $this->completed_at
                ? Carbon::parse($this->completed_at)->format('Y-m-d H:i:s')
                : null,
            'reward_amount' => $this->reward_amount,
            'supervisor'    => [
                'id'   => $this->whenLoaded('supervisor') ? $this->supervisor->id : null,
                'name' => $this->whenLoaded('supervisor') && $this->supervisor->profile
                    ? $this->supervisor->profile->first_name . ' ' . $this->supervisor->profile->last_name
                    : null,
            ],
        ];
    }
}