<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'department' => $this->department,
            'contract_hours' => $this->contract_hours,
            'team' => $this->whenLoaded('team', fn () => [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'is_demo' => $this->team->is_demo,
            ]),
        ];
    }
}
