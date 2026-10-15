<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;

class ShiftPolicy
{
    public function create(User $user): bool
    {
        return $user->isManager();
    }

    public function update(User $user, Shift $shift): bool
    {
        return $user->isManager() && $user->team_id === $shift->team_id;
    }

    public function delete(User $user, Shift $shift): bool
    {
        return $this->update($user, $shift);
    }

    /** Only the person working the shift can offer it to colleagues. */
    public function offer(User $user, Shift $shift): bool
    {
        return $user->id === $shift->user_id;
    }
}
