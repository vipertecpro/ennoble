<?php

use App\Domain\Achievements\AchievementService;
use App\Enums\Difficulty;
use App\Models\Profile as LocalProfile;
use App\Models\Setting;
use App\Models\Statistic;
use App\NativeComponents\Screens\AchievementCategory;
use App\NativeComponents\Screens\Achievements;
use Carbon\CarbonImmutable;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-07-18 09:30:00');

    $this->profile = LocalProfile::factory()->onboarded()->create([
        'display_name' => 'Ada',
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create([
        'reduced_motion' => false,
    ]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('a first-time achievements screen shows the full catalogue total and every category', function () {
    Native::visit('/achievements')
        ->assertScreen(Achievements::class)
        ->assertSee('BADGES EARNED')
        ->assertSee('175')
        ->assertSee('Streaks')
        ->assertSee('Accuracy')
        ->assertSee('Speed')
        ->assertSee('Dedication')
        ->assertSee('Mastery')
        ->assertSee('Your stats')
        ->assertSet('totalEarned', 0)
        ->assertSet('totalBadges', 175)
        ->assertAccessible();
});

test('earned badges are reflected in the achievements board totals', function () {
    Statistic::factory()->for($this->profile)->create([
        'scope_key' => 'overall',
        'sessions_completed' => 4,
        'current_streak' => 5,
        'accuracy' => 70,
        'average_response_ms' => 2500,
        'best_score' => 900,
    ]);
    app(AchievementService::class)->evaluate($this->profile);

    $screen = Native::visit('/achievements')
        ->assertScreen(Achievements::class)
        ->assertSee('175')
        ->assertSet('totalBadges', 175)
        ->assertSee('Streaks')
        ->assertAccessible();

    expect($screen->get('totalEarned'))->toBeGreaterThan(0);
});

test('an incomplete profile is returned to onboarding before achievements load', function () {
    $this->profile->update(['onboarding_completed_at' => null]);

    Native::visit('/achievements')
        ->assertReplacedWith('/onboarding');
});

test('reduced motion removes authored achievement durations', function () {
    Setting::query()
        ->whereBelongsTo($this->profile)
        ->update(['reduced_motion' => true]);

    Native::visit('/achievements')
        ->assertSet('reducedMotion', true)
        ->assertSet('motionDuration', 0)
        ->assertAccessible();
});

test('a badge category screen renders its tier sections without error', function () {
    Statistic::factory()->for($this->profile)->create([
        'scope_key' => 'overall',
        'current_streak' => 6,
    ]);
    app(AchievementService::class)->evaluate($this->profile);

    Native::visit('/achievements/streak')
        ->assertScreen(AchievementCategory::class)
        ->assertSet('screenState', 'content')
        ->assertSee('Bronze')
        ->assertSee('Silver')
        ->assertSee('Gold')
        ->assertSee('EARNED')
        ->assertAccessible();
});

test('an unknown badge category renders the recoverable error state', function () {
    Native::visit('/achievements/not-a-category')
        ->assertScreen(AchievementCategory::class)
        ->assertSet('screenState', 'error')
        ->assertSee('This badge category could not be found.');
});
