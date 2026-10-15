<?php

namespace App\Models;

use App\Support\Rota\ShiftSlot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    public const DEPARTMENTS = ['checkout', 'fresh', 'stock', 'bakery'];

    protected $fillable = ['team_id', 'user_id', 'date', 'start_time', 'end_time', 'department', 'note'];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function swapRequests(): HasMany
    {
        return $this->hasMany(SwapRequest::class);
    }

    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date->format('Y-m-d').' '.$this->start_time);
    }

    public function endsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date->format('Y-m-d').' '.$this->end_time);
    }

    public function hours(): float
    {
        return round($this->startsAt()->diffInMinutes($this->endsAt()) / 60, 2);
    }

    public function toSlot(): ShiftSlot
    {
        return new ShiftSlot($this->id, $this->user_id, $this->startsAt(), $this->endsAt());
    }
}
