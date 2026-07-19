<?php

namespace App\Models;

use App\Enums\GameStatus;
use App\Enums\GameType;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'slug',
        'name',
        'description',
        'status',
        'sort_order',
        'skill_keys',
        'configuration',
    ];

    protected $attributes = [
        'status' => 'playable',
    ];

    public function levels(): HasMany
    {
        return $this->hasMany(GameLevel::class);
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function statistics(): HasMany
    {
        return $this->hasMany(Statistic::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    public function scopePlayable(Builder $query): Builder
    {
        return $query->where('status', GameStatus::Playable);
    }

    protected function casts(): array
    {
        return [
            'type' => GameType::class,
            'status' => GameStatus::class,
            'skill_keys' => 'array',
            'configuration' => 'array',
        ];
    }
}
