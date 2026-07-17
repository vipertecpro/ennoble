<?php

namespace App\Models;

use App\Enums\WorkoutStatus;
use Database\Factories\DailyWorkoutItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyWorkoutItem extends Model
{
    /** @use HasFactory<DailyWorkoutItemFactory> */
    use HasFactory;

    protected $fillable = [
        'daily_workout_id',
        'game_id',
        'game_level_id',
        'position',
        'status',
        'configuration',
        'started_at',
        'completed_at',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    public function workout(): BelongsTo
    {
        return $this->belongsTo(DailyWorkout::class, 'daily_workout_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(GameLevel::class, 'game_level_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    protected function casts(): array
    {
        return [
            'status' => WorkoutStatus::class,
            'configuration' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
