<?php

namespace App\Models;

use App\Enums\WorkoutStatus;
use Carbon\CarbonInterface;
use Database\Factories\DailyWorkoutFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyWorkout extends Model
{
    /** @use HasFactory<DailyWorkoutFactory> */
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'workout_date',
        'status',
        'generation_version',
        'started_at',
        'completed_at',
        'statistics_recorded_at',
        'training_seconds',
        'accuracy',
        'summary',
    ];

    protected $attributes = [
        'status' => 'pending',
        'generation_version' => 1,
        'training_seconds' => 0,
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DailyWorkoutItem::class)->orderBy('position');
    }

    public function achievementUnlocks(): HasMany
    {
        return $this->hasMany(AchievementUnlock::class);
    }

    public function scopeForDate(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query->whereDate('workout_date', $date);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', WorkoutStatus::Completed);
    }

    public function scopeResumable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            WorkoutStatus::Pending,
            WorkoutStatus::InProgress,
        ]);
    }

    protected function casts(): array
    {
        return [
            'workout_date' => 'date',
            'status' => WorkoutStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'statistics_recorded_at' => 'datetime',
            'accuracy' => 'float',
            'summary' => 'array',
        ];
    }
}
