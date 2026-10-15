<?php

namespace App\Policies;

use App\Models\SwapRequest;
use App\Models\User;

class SwapRequestPolicy
{
    public function pickUp(User $user, SwapRequest $swap): bool
    {
        return $swap->status === SwapRequest::OPEN
            && $user->id !== $swap->requester_id
            && ! $user->isManager()
            && $user->team_id === $swap->requester->team_id;
    }

    public function review(User $user, SwapRequest $swap): bool
    {
        return $user->isManager()
            && $swap->status === SwapRequest::PENDING
            && $user->team_id === $swap->requester->team_id;
    }

    public function cancel(User $user, SwapRequest $swap): bool
    {
        return $user->id === $swap->requester_id && $swap->isActive();
    }
}
