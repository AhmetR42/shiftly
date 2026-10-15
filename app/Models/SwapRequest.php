<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwapRequest extends Model
{
    public const OPEN = 'open';

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const DECLINED = 'declined';

    public const CANCELLED = 'cancelled';

    protected $attributes = ['status' => self::OPEN];

    protected $fillable = ['shift_id', 'requester_id', 'taker_id', 'status', 'note'];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function taker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taker_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::OPEN, self::PENDING], true);
    }
}
