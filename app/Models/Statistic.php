<?php

namespace App\Models;

use Database\Factories\StatisticFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Statistic extends Model
{
    /** @use HasFactory<StatisticFactory> */
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'game_id',
        'scope_key',
        'sessions_completed',
        'workouts_completed',
        'training_seconds',
        'correct_count',
        'attempted_count',
        'total_response_ms',
        'response_count',
        'accuracy',
        'average_response_ms',
        'best_score',
        'longest_combo',
        'current_streak',
        'longest_streak',
        'last_workout_date',
        'last_calculated_at',
    ];

    protected $attributes = [
        'sessions_completed' => 0,
        'workouts_completed' => 0,
        'training_seconds' => 0,
        'correct_count' => 0,
        'attempted_count' => 0,
        'total_response_ms' => 0,
        'response_count' => 0,
        'longest_combo' => 0,
        'current_streak' => 0,
        'longest_streak' => 0,
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function scopeOverall(Builder $query): Builder
    {
        return $query->where('scope_key', 'overall');
    }

    protected function casts(): array
    {
        return [
            'accuracy' => 'float',
            'last_workout_date' => 'date',
            'last_calculated_at' => 'datetime',
        ];
    }
}
