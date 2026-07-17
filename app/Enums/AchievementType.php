<?php

namespace App\Enums;

enum AchievementType: string
{
    case FirstWorkout = 'first_workout';
    case WorkoutStreak = 'workout_streak';
    case Accuracy = 'accuracy';
    case Score = 'score';
    case Combo = 'combo';
    case HintFree = 'hint_free';
}
