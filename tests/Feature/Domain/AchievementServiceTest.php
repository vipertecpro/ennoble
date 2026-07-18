<?php

use App\Domain\Achievements\AchievementService;
use App\Enums\AchievementType;
use App\Enums\GameType;
use App\Enums\SessionStatus;
use App\Models\Achievement;
use App\Models\AchievementUnlock;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Statistic;

test('achievement evaluation respects thresholds and remains idempotent', function () {
    $profile = Profile::factory()->create();
    $service = app(AchievementService::class);

    expect($service->evaluate($profile))->toBeEmpty();

    Statistic::factory()->for($profile)->create([
        'scope_key' => 'overall',
        'workouts_completed' => 1,
        'accuracy' => 90,
        'current_streak' => 3,
        'longest_streak' => 3,
    ]);

    $firstPass = $service->evaluate($profile);
    $secondPass = $service->evaluate($profile);

    expect($firstPass->pluck('achievement.slug')->sort()->values()->all())->toBe([
        'first-step',
        'precision-minded',
        'steady-three',
    ])
        ->and($secondPass)->toBeEmpty()
        ->and(AchievementUnlock::query()->whereBelongsTo($profile)->count())->toBe(3);
});

test('game achievements use persisted bests and hint-free session evidence', function () {
    $profile = Profile::factory()->create();
    $signalShift = Game::query()->where('type', GameType::SignalShift)->firstOrFail();
    $clearThought = Game::query()->where('type', GameType::ClearThought)->firstOrFail();
    Statistic::factory()->for($profile)->create([
        'game_id' => $signalShift->getKey(),
        'scope_key' => 'game:signal_shift',
        'best_score' => 1500,
        'longest_combo' => 8,
    ]);
    $clearSession = GameSession::factory()->create([
        'profile_id' => $profile->getKey(),
        'game_id' => $clearThought->getKey(),
        'game_level_id' => $clearThought->levels()->firstOrFail()->getKey(),
        'status' => SessionStatus::Completed,
        'correct_count' => 1,
        'hint_count' => 0,
        'completed_at' => now(),
    ]);

    $unlocks = app(AchievementService::class)->evaluate(
        profile: $profile,
        gameSession: $clearSession,
    );

    expect($unlocks->pluck('achievement.slug')->sort()->values()->all())->toBe([
        'clear-without-hints',
        'signal-master',
        'signal-momentum',
    ]);
});

test('inactive achievement definitions are never awarded', function () {
    $profile = Profile::factory()->create();
    Statistic::factory()->for($profile)->create([
        'workouts_completed' => 100,
    ]);
    $inactive = Achievement::factory()->create([
        'slug' => 'retired-milestone',
        'type' => AchievementType::FirstWorkout,
        'criterion' => ['workouts' => 1],
        'is_active' => false,
        'sort_order' => 99,
    ]);

    app(AchievementService::class)->evaluate($profile);

    expect(AchievementUnlock::query()
        ->whereBelongsTo($profile)
        ->whereBelongsTo($inactive, 'achievement')
        ->exists())->toBeFalse();
});

test('the achievement overview lists every active definition with profile-scoped unlocks', function () {
    $profile = Profile::factory()->create();
    $other = Profile::factory()->create(['singleton_key' => 'other-device']);
    $first = Achievement::query()->where('slug', 'first-step')->firstOrFail();

    AchievementUnlock::factory()->for($profile)->for($first)->create();
    AchievementUnlock::factory()
        ->for($other)
        ->for(Achievement::query()->where('slug', 'signal-master')->firstOrFail())
        ->create();
    Achievement::factory()->create([
        'slug' => 'retired-overview',
        'type' => AchievementType::FirstWorkout,
        'criterion' => ['workouts' => 1],
        'is_active' => false,
        'sort_order' => 98,
    ]);

    $overview = app(AchievementService::class)->overview($profile);

    expect($overview->pluck('slug'))->not->toContain('retired-overview')
        ->and($overview->firstWhere('slug', 'first-step')->unlocks)->toHaveCount(1)
        ->and($overview->firstWhere('slug', 'signal-master')->unlocks)->toBeEmpty()
        ->and($overview->count())->toBe(6);
});
