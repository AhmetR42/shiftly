<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_demo'];

    protected function casts(): array
    {
        return ['is_demo' => 'boolean'];
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
