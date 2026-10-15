<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $fillable = ['team_id', 'user_id', 'type', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Small helper so controllers can log in one line.
     *
     * @param  array<string, mixed>  $data
     */
    public static function record(User $actor, string $type, array $data = []): self
    {
        return self::create([
            'team_id' => $actor->team_id,
            'user_id' => $actor->id,
            'type' => $type,
            'data' => $data,
        ]);
    }
}
