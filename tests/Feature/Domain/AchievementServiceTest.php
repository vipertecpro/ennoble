<?php

use App\Domain\Achievements\AchievementService;
use App\Enums\AchievementTier;
use App\Enums\AchievementType;
use App\Models\Achievement;
use App\Models\AchievementUnlock;
use App\Models\Profile;
use App\Models\Statistic;
use Illuminate\Support\Collection;

/**
 * Count this profile's unlocks grouped by the badge category they belong to.
 *
 * @return array<string, int>
 */
function unlocksByType(Profile $profile): array
{
    return AchievementUnlock::query()
        ->whereBelongsTo($profile)
        ->with('achievement')
        ->get()
        ->groupBy(fn (AchievementUnlock $unlock): string => $unlock->achievement->type->value)
        ->map(fn (Collection $group): int => $group->count())
        ->all();
}

test('the seeded catalogue is available to evaluate against', function () {
    expect(Achievement::query()->count())->toBe(175);
});

test('evaluation unlocks every badge whose threshold the overall metric clears', function () {
    $profile = Profile::factory()->create();
    Statistic::factory()->for($profile)->create([
        'scope_key' => 'overall',
        'sessions_completed' => 3,
        'current_streak' => 5,
        'accuracy' => 60,
        'average_response_ms' => 3000,
        'best_score' => 500,
    ]);

    $unlocked = app(AchievementService::class)->evaluate($profile);

    // Streak >= 5 clears the 2,3,4,5 rungs; accuracy >= 60 clears 40..60 (11 rungs);
    // speed <= 3000 clears the 5000..3000 rungs (11); dedication >= 3 clears 1,2,3;
    // mastery >= 500 clears 100..500 (5).
    expect(unlocksByType($profile))->toBe([
        AchievementType::Streak->value => 4,
        AchievementType::Accuracy->value => 11,
        AchievementType::Speed->value => 11,
        AchievementType::Dedication->value => 3,
        AchievementType::Mastery->value => 5,
    ])
        ->and($unlocked)->toHaveCount(4 + 11 + 11 + 3 + 5);

    // A harder streak rung (6 days) stays locked.
    $sixDayStreak = Achievement::query()
        ->where('type', AchievementType::Streak)
        ->whereJsonContains('criterion->threshold', 6)
        ->firstOrFail();

    expect(AchievementUnlock::query()
        ->whereBelongsTo($profile)
        ->whereBelongsTo($sixDayStreak, 'achievement')
        ->exists())->toBeFalse();
});

test('a fast average response clears the easy high-ms speed badges and cascades to the hardest', function () {
    $profile = Profile::factory()->create();
    Statistic::factory()->for($profile)->create([
        'scope_key' => 'overall',
        'average_response_ms' => 600,
    ]);

    app(AchievementService::class)->evaluate($profile);

    $speedUnlocks = AchievementUnlock::query()
        ->whereBelongsTo($profile)
        ->with('achievement')
        ->get()
        ->filter(fn (AchievementUnlock $unlock): bool => $unlock->achievement->type === AchievementType::Speed);

    // 600 ms is the hardest rung, so <= clears every speed badge including Gold.
    expect($speedUnlocks)->toHaveCount(35)
        ->and($speedUnlocks->contains(
            fn (AchievementUnlock $unlock): bool => $unlock->achievement->tier === AchievementTier::Gold,
        ))->toBeTrue();

    // Only the speed category responds to a speed-only statistic.
    expect(unlocksByType($profile))->toBe([AchievementType::Speed->value => 35]);
});

test('reaching a gold threshold cascades to unlock every lower bronze and silver badge in that category', function () {
    $profile = Profile::factory()->create();
    Statistic::factory()->for($profile)->create([
        'scope_key' => 'overall',
        'best_score' => 12000,
    ]);

    app(AchievementService::class)->evaluate($profile);

    $masteryUnlocks = AchievementUnlock::query()
        ->whereBelongsTo($profile)
        ->with('achievement')
        ->get()
        ->filter(fn (AchievementUnlock $unlock): bool => $unlock->achievement->type === AchievementType::Mastery)
        ->groupBy(fn (AchievementUnlock $unlock): string => $unlock->achievement->tier->value)
        ->map(fn (Collection $group): int => $group->count());

    expect($masteryUnlocks->all())->toBe([
        AchievementTier::Bronze->value => 20,
        AchievementTier::Silver->value => 10,
        AchievementTier::Gold->value => 5,
    ]);
});

test('a profile with no evidence unlocks nothing', function () {
    $profile = Profile::factory()->create();

    expect(app(AchievementService::class)->evaluate($profile))->toBeEmpty()
        ->and(AchievementUnlock::query()->whereBelongsTo($profile)->count())->toBe(0);
});

test('null metrics never clear a badge, including the less-than-or-equal speed comparator', function () {
    $profile = Profile::factory()->create();
    Statistic::factory()->for($profile)->create([
        'scope_key' => 'overall',
        'current_streak' => 0,
        'accuracy' => null,
        'average_response_ms' => null,
        'sessions_completed' => 0,
        'best_score' => null,
    ]);

    expect(app(AchievementService::class)->evaluate($profile))->toBeEmpty()
        ->and(AchievementUnlock::query()->whereBelongsTo($profile)->count())->toBe(0);
});

test('evaluation is idempotent and never duplicates an unlock', function () {
    $profile = Profile::factory()->create();
    Statistic::factory()->for($profile)->create([
        'scope_key' => 'overall',
        'current_streak' => 10,
    ]);
    $service = app(AchievementService::class);

    $firstPass = $service->evaluate($profile);
    $secondPass = $service->evaluate($profile);

    expect($firstPass)->not->toBeEmpty()
        ->and($secondPass)->toBeEmpty()
        ->and(AchievementUnlock::query()->whereBelongsTo($profile)->count())
        ->toBe($firstPass->count());
});

test('inactive badge definitions are never awarded', function () {
    $profile = Profile::factory()->create();
    Statistic::factory()->for($profile)->create([
        'scope_key' => 'overall',
        'current_streak' => 50,
    ]);
    $inactive = Achievement::factory()
        ->type(AchievementType::Streak)
        ->threshold(1)
        ->create([
            'slug' => 'retired-streak',
            'is_active' => false,
            'sort_order' => 999,
        ]);

    app(AchievementService::class)->evaluate($profile);

    expect(AchievementUnlock::query()
        ->whereBelongsTo($profile)
        ->whereBelongsTo($inactive, 'achievement')
        ->exists())->toBeFalse();
});

test('the board summarises earned badges by tier and category', function () {
    $profile = Profile::factory()->create();
    Statistic::factory()->for($profile)->create([
        'scope_key' => 'overall',
        'sessions_completed' => 2,
        'current_streak' => 3,
        'accuracy' => 50,
        'average_response_ms' => 2500,
        'best_score' => 400,
    ]);
    app(AchievementService::class)->evaluate($profile);

    $board = app(AchievementService::class)->board($profile);

    expect($board['total'])->toBe(175)
        ->and($board['earned'])->toBeGreaterThan(0)
        ->and($board['tiers'])->toHaveKeys(['bronze', 'silver', 'gold'])
        ->and($board['categories'])->toHaveCount(5);

    $streakCategory = collect($board['categories'])
        ->firstWhere('key', AchievementType::Streak->value);

    expect($streakCategory['label'])->toBe('Streaks')
        ->and($streakCategory['total'])->toBe(35);
});

test('the overview lists every active badge with profile-scoped unlocks eager-loaded', function () {
    $profile = Profile::factory()->create();
    $other = Profile::factory()->create(['singleton_key' => 'other-device']);
    $badge = Achievement::query()->orderBy('sort_order')->firstOrFail();

    AchievementUnlock::factory()->for($profile)->for($badge)->create();
    AchievementUnlock::factory()->for($other)->for($badge)->create();

    $overview = app(AchievementService::class)->overview($profile);

    expect($overview)->toHaveCount(175)
        ->and($overview->firstWhere('id', $badge->getKey())->unlocks)->toHaveCount(1);
});
