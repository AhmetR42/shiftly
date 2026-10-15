<?php

namespace App\Policies;

use App\Models\TimeOffRequest;
use App\Models\User;

class TimeOffRequestPolicy
{
    public function review(User $user, TimeOffRequest $request): bool
    {
        return $user->isManager()
            && $request->status === 'pending'
            && $user->team_id === $request->user->team_id;
    }

    public function delete(User $user, TimeOffRequest $request): bool
    {
        return $user->id === $request->user_id && $request->status === 'pending';
    }
}
