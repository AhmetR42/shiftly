<?php

namespace App\Support\Rota;

use DateTimeImmutable;

/**
 * A shift reduced to the bits the rules care about: who works, and when.
 * Keeping this free of Eloquent makes the rules trivial to unit test.
 */
final class ShiftSlot
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly DateTimeImmutable $start,
        public readonly DateTimeImmutable $end,
    ) {}

    public function hours(): float
    {
        return ($this->end->getTimestamp() - $this->start->getTimestamp()) / 3600;
    }
}
