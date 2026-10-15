<?php

namespace App\Support\Rota;

final class Warning
{
    public const OVERLAP = 'overlap';

    public const SHORT_REST = 'short_rest';

    public const OVER_CONTRACT = 'over_contract';

    /**
     * @param  list<int>  $shiftIds
     */
    public function __construct(
        public readonly string $type,
        public readonly int $userId,
        public readonly array $shiftIds = [],
        public readonly ?float $value = null,
    ) {}

    /**
     * @return array{type: string, user_id: int, shift_ids: list<int>, value: float|null}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'user_id' => $this->userId,
            'shift_ids' => $this->shiftIds,
            'value' => $this->value,
        ];
    }
}
