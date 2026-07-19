<?php

use App\Enums\AchievementTier;
use App\Enums\AchievementType;
use App\Enums\Difficulty;
use App\Enums\GameStatus;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Enums\SkillKey;
use App\Enums\ThemePreference;
use App\Enums\TrainingGoal;

test('domain enums expose stable unique persisted values', function (string $enumClass, array $expectedValues) {
    $actualValues = array_map(
        static fn (BackedEnum $case): string => $case->value,
        $enumClass::cases(),
    );

    expect($actualValues)
        ->toBe($expectedValues)
        ->and(array_unique($actualValues))->toHaveCount(count($actualValues));
})->with([
    'game types' => [GameType::class, ['word_match', 'quick_math']],
    'difficulty' => [Difficulty::class, ['beginner', 'intermediate', 'advanced', 'adaptive']],
    'game status' => [GameStatus::class, ['playable', 'coming_soon']],
    'session status' => [SessionStatus::class, ['in_progress', 'completed', 'abandoned', 'invalid']],
    'round outcomes' => [RoundOutcome::class, ['correct', 'incorrect', 'missed']],
    'achievement types' => [AchievementType::class, ['streak', 'accuracy', 'speed', 'dedication', 'mastery']],
    'achievement tiers' => [AchievementTier::class, ['bronze', 'silver', 'gold']],
    'themes' => [ThemePreference::class, ['system', 'light', 'dark']],
    'training goals' => [TrainingGoal::class, ['balanced', 'focus', 'thinking_speed', 'language', 'mental_sharpness']],
    'skill keys' => [SkillKey::class, ['focus', 'speed', 'precision', 'adaptability', 'clarity', 'structure', 'critical_reading']],
]);

test('achievement tiers describe their rank, catalogue size, and medal colour', function () {
    expect(AchievementTier::ascending())->toBe([
        AchievementTier::Bronze,
        AchievementTier::Silver,
        AchievementTier::Gold,
    ]);

    expect(AchievementTier::Bronze->label())->toBe('Bronze')
        ->and(AchievementTier::Silver->label())->toBe('Silver')
        ->and(AchievementTier::Gold->label())->toBe('Gold');

    expect(AchievementTier::Bronze->rank())->toBe(1)
        ->and(AchievementTier::Silver->rank())->toBe(2)
        ->and(AchievementTier::Gold->rank())->toBe(3);

    expect(AchievementTier::Bronze->badgesPerCategory())->toBe(20)
        ->and(AchievementTier::Silver->badgesPerCategory())->toBe(10)
        ->and(AchievementTier::Gold->badgesPerCategory())->toBe(5);

    expect(AchievementTier::Bronze->colorToken())->toBe('badge-bronze')
        ->and(AchievementTier::Silver->colorToken())->toBe('badge-silver')
        ->and(AchievementTier::Gold->colorToken())->toBe('badge-gold');
});

test('achievement types map to the overall statistic they measure', function () {
    expect(AchievementType::Streak->metric())->toBe('current_streak')
        ->and(AchievementType::Accuracy->metric())->toBe('accuracy')
        ->and(AchievementType::Speed->metric())->toBe('average_response_ms')
        ->and(AchievementType::Dedication->metric())->toBe('sessions_completed')
        ->and(AchievementType::Mastery->metric())->toBe('best_score');
});

test('only the speed category clears on a lower measured value', function () {
    expect(AchievementType::Speed->comparator())->toBe('<=');

    foreach ([
        AchievementType::Streak,
        AchievementType::Accuracy,
        AchievementType::Dedication,
        AchievementType::Mastery,
    ] as $type) {
        expect($type->comparator())->toBe('>=');
    }
});

test('achievement types label, tagline, and format their values honestly', function () {
    expect(AchievementType::Streak->label())->toBe('Streaks')
        ->and(AchievementType::Accuracy->label())->toBe('Accuracy')
        ->and(AchievementType::Speed->label())->toBe('Speed')
        ->and(AchievementType::Dedication->label())->toBe('Dedication')
        ->and(AchievementType::Mastery->label())->toBe('Mastery');

    expect(AchievementType::Streak->formatValue(1))->toBe('1 day')
        ->and(AchievementType::Streak->formatValue(7))->toBe('7 days')
        ->and(AchievementType::Accuracy->formatValue(83.4))->toBe('83%')
        ->and(AchievementType::Speed->formatValue(900))->toBe('900 ms')
        ->and(AchievementType::Speed->formatValue(null))->toBe('Not measured')
        ->and(AchievementType::Dedication->formatValue(1))->toBe('1 game')
        ->and(AchievementType::Dedication->formatValue(12))->toBe('12 games')
        ->and(AchievementType::Mastery->formatValue(12000))->toBe('12,000 pts')
        ->and(AchievementType::Streak->formatValue(null))->toBe('0');

    expect(AchievementType::Streak->tagline())->not->toBe('');
});
