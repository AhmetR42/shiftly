<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Availability extends Model
{
    protected $fillable = ['user_id', 'weekday', 'available', 'from_time', 'until_time'];

    protected function casts(): array
    {
        return ['available' => 'boolean', 'weekday' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
