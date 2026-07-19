<?php

namespace App\Models;

use App\Enums\AchievementTier;
use App\Enums\AchievementType;
use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    /** @use HasFactory<AchievementFactory> */
    use HasFactory;

    protected $fillable = [
        'game_id',
        'slug',
        'name',
        'description',
        'type',
        'tier',
        'criterion',
        'sort_order',
        'is_active',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(AchievementUnlock::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'type' => AchievementType::class,
            'tier' => AchievementTier::class,
            'criterion' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
