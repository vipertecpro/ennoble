<?php

use App\Enums\AchievementType;
use App\Enums\ClearThoughtMode;
use App\Enums\Difficulty;
use App\Enums\GameStatus;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Enums\SkillKey;
use App\Enums\ThemePreference;
use App\Enums\TrainingGoal;
use App\Enums\WorkoutStatus;

test('domain enums expose stable unique persisted values', function (string $enumClass, array $expectedValues) {
    $actualValues = array_map(
        static fn (BackedEnum $case): string => $case->value,
        $enumClass::cases(),
    );

    expect($actualValues)
        ->toBe($expectedValues)
        ->and(array_unique($actualValues))->toHaveCount(count($actualValues));
})->with([
    'game types' => [GameType::class, ['signal_shift', 'clear_thought', 'word_match', 'quick_math']],
    'difficulty' => [Difficulty::class, ['beginner', 'intermediate', 'advanced', 'adaptive']],
    'game status' => [GameStatus::class, ['playable', 'coming_soon']],
    'workout status' => [WorkoutStatus::class, ['pending', 'in_progress', 'completed']],
    'session status' => [SessionStatus::class, ['in_progress', 'completed', 'abandoned', 'invalid']],
    'round outcomes' => [RoundOutcome::class, ['correct', 'incorrect', 'missed']],
    'achievement types' => [AchievementType::class, ['first_workout', 'workout_streak', 'accuracy', 'score', 'combo', 'hint_free']],
    'themes' => [ThemePreference::class, ['system', 'light', 'dark']],
    'training goals' => [TrainingGoal::class, ['balanced', 'focus', 'thinking_speed', 'language', 'mental_sharpness']],
    'clear thought modes' => [ClearThoughtMode::class, ['remove_unnecessary_words', 'reorder_sentence', 'choose_clearest_sentence']],
    'skill keys' => [SkillKey::class, ['focus', 'speed', 'precision', 'adaptability', 'clarity', 'structure', 'critical_reading']],
]);
