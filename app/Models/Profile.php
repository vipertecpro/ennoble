<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\TrainingGoal;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'singleton_key',
        'display_name',
        'training_goal',
        'difficulty_preference',
        'onboarding_completed_at',
    ];

    protected $attributes = [
        'singleton_key' => 'local',
        'training_goal' => 'balanced',
        'difficulty_preference' => 'intermediate',
    ];

    public function setting(): HasOne
    {
        return $this->hasOne(Setting::class);
    }

    public function dailyWorkouts(): HasMany
    {
        return $this->hasMany(DailyWorkout::class);
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function progressSnapshots(): HasMany
    {
        return $this->hasMany(ProgressSnapshot::class);
    }

    public function statistics(): HasMany
    {
        return $this->hasMany(Statistic::class);
    }

    public function achievementUnlocks(): HasMany
    {
        return $this->hasMany(AchievementUnlock::class);
    }

    protected function casts(): array
    {
        return [
            'training_goal' => TrainingGoal::class,
            'difficulty_preference' => Difficulty::class,
            'onboarding_completed_at' => 'datetime',
        ];
    }
}
