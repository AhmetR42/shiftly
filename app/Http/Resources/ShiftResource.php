<?php

namespace App\Http\Resources;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Shift */
class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'date' => $this->date->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'department' => $this->department,
            'note' => $this->note,
            'hours' => $this->hours(),
            'swap_status' => $this->whenLoaded(
                'swapRequests',
                fn () => $this->swapRequests->first(fn ($swap) => $swap->isActive())?->status,
            ),
        ];
    }
}
