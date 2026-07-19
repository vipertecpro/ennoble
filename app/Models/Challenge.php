<?php

namespace App\Models;

use Database\Factories\ChallengeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    /** @use HasFactory<ChallengeFactory> */
    use HasFactory;

    protected $fillable = [
        'game_id',
        'game_level_id',
        'slug',
        'mode',
        'content_version',
        'prompt',
        'payload',
        'accepted_answers',
        'explanation',
        'hint',
        'is_active',
    ];

    protected $attributes = [
        'content_version' => 1,
        'is_active' => true,
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(GameLevel::class, 'game_level_id');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(GameRound::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'accepted_answers' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
