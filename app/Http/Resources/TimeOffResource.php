<?php

namespace App\Http\Resources;

use App\Models\TimeOffRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TimeOffRequest */
class TimeOffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => ['id' => $this->user_id, 'name' => $this->user?->name],
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'days' => (int) $this->start_date->diffInDays($this->end_date) + 1,
            'reason' => $this->reason,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
