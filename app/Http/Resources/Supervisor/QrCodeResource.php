<?php

namespace App\Http\Resources\Supervisor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class QrCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code'       => $this->code,
            'type'       => $this->type,
            'expires_at' => Carbon::parse($this->expires_at)->format('Y-m-d H:i:s'),
        ];
    }
}