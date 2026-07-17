<?php

namespace App\Models;

use App\Enums\Difficulty;
use Database\Factories\GameLevelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameLevel extends Model
{
    /** @use HasFactory<GameLevelFactory> */
    use HasFactory;

    protected $fillable = [
        'game_id',
        'difficulty',
        'name',
        'round_count',
        'target_response_ms',
        'configuration',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    public function workoutItems(): HasMany
    {
        return $this->hasMany(DailyWorkoutItem::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'difficulty' => Difficulty::class,
            'configuration' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
