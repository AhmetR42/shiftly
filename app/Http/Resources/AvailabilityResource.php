<?php

namespace App\Http\Resources;

use App\Models\Availability;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Availability */
class AvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'weekday' => $this->weekday,
            'available' => $this->available,
            'from_time' => $this->from_time,
            'until_time' => $this->until_time,
        ];
    }
}
