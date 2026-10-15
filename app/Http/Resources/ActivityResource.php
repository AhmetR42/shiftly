<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Activity */
class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'actor' => $this->user?->name,
            'data' => $this->data ?? (object) [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
