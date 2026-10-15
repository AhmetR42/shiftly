<?php

namespace App\Http\Resources;

use App\Models\SwapRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SwapRequest */
class SwapRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'note' => $this->note,
            'shift' => new ShiftResource($this->whenLoaded('shift')),
            'requester' => ['id' => $this->requester_id, 'name' => $this->requester?->name],
            'taker' => $this->taker_id ? ['id' => $this->taker_id, 'name' => $this->taker?->name] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
