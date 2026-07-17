<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Database\Factories\GameSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    /** @use HasFactory<GameSessionFactory> */
    use HasFactory;

    public const FRAMEWORK_PLACEHOLDER_MODE = 'workout_framework_placeholder';

    protected $fillable = [
        'profile_id',
        'game_id',
        'game_level_id',
        'daily_workout_item_id',
        'status',
        'mode',
        'snapshot_version',
        'current_round',
        'state_snapshot',
        'score',
        'accuracy',
        'average_response_ms',
        'correct_count',
        'incorrect_count',
        'missed_count',
        'hint_count',
        'best_combo',
        'started_at',
        'last_interaction_at',
        'completed_at',
        'statistics_recorded_at',
    ];

    protected $attributes = [
        'status' => 'in_progress',
        'snapshot_version' => 1,
        'current_round' => 0,
        'correct_count' => 0,
        'incorrect_count' => 0,
        'missed_count' => 0,
        'hint_count' => 0,
        'best_combo' => 0,
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(GameLevel::class, 'game_level_id');
    }

    public function workoutItem(): BelongsTo
    {
        return $this->belongsTo(DailyWorkoutItem::class, 'daily_workout_item_id');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(GameRound::class)->orderBy('round_number');
    }

    public function progressSnapshots(): HasMany
    {
        return $this->hasMany(ProgressSnapshot::class);
    }

    public function achievementUnlocks(): HasMany
    {
        return $this->hasMany(AchievementUnlock::class);
    }

    public function scopeResumable(Builder $query): Builder
    {
        return $query->where('status', SessionStatus::InProgress);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', SessionStatus::Completed);
    }

    public function scopeWithGameplayEvidence(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->whereNull('mode')
                ->orWhere('mode', '!=', self::FRAMEWORK_PLACEHOLDER_MODE);
        });
    }

    public function isFrameworkPlaceholder(): bool
    {
        return $this->mode === self::FRAMEWORK_PLACEHOLDER_MODE;
    }

    protected function casts(): array
    {
        return [
            'status' => SessionStatus::class,
            'state_snapshot' => 'array',
            'accuracy' => 'float',
            'started_at' => 'datetime',
            'last_interaction_at' => 'datetime',
            'completed_at' => 'datetime',
            'statistics_recorded_at' => 'datetime',
        ];
    }
}
