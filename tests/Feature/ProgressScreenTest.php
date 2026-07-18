<?php

use App\Enums\Difficulty;
use App\Enums\SkillKey;
use App\Models\Achievement;
use App\Models\AchievementUnlock;
use App\Models\DailyWorkout;
use App\Models\Game;
use App\Models\Profile as LocalProfile;
use App\Models\ProgressSnapshot;
use App\Models\Setting;
use App\Models\Statistic;
use App\NativeComponents\Screens\Progress;
use Carbon\CarbonImmutable;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-07-18 09:30:00');

    $this->profile = LocalProfile::factory()->onboarded()->create([
        'display_name' => 'Ada',
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create([
        'haptics_enabled' => true,
        'reduced_motion' => false,
    ]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('a first-time progress screen renders honest empty evidence states', function () {
    Native::visit('/progress')
        ->assertScreen(Progress::class)
        ->assertSee('QUIET EVIDENCE')
        ->assertSee('Progress you can trust.')
        ->assertSee('Ready for day one')
        ->assertSee('0 of the last 7 days')
        ->assertSee('No skill progress yet')
        ->assertSee('No training evidence yet')
        ->assertSee('0 OF 6 UNLOCKED')
        ->assertSee('First Step')
        ->assertSee('Locked')
        ->assertDontSee('Personal bests')
        ->assertDontSee('Loading your progress')
        ->assertDontSee('Something went wrong')
        ->assertAccessible();
});

test('a returning user sees rhythm skill training and achievement evidence', function () {
    DailyWorkout::factory()->for($this->profile)->completed()->create([
        'workout_date' => today()->subDay(),
    ]);
    DailyWorkout::factory()->for($this->profile)->completed()->create([
        'workout_date' => today(),
    ]);
    Statistic::factory()->for($this->profile)->create([
        'scope_key' => 'overall',
        'current_streak' => 2,
        'longest_streak' => 5,
        'workouts_completed' => 8,
        'sessions_completed' => 16,
        'training_seconds' => 2520,
        'accuracy' => 83.3,
        'average_response_ms' => 1410,
        'longest_combo' => 6,
    ]);

    $signalShift = Game::query()->where('slug', 'signal-shift')->firstOrFail();

    Statistic::factory()->for($this->profile)->for($signalShift)->create([
        'scope_key' => 'game:signal_shift',
        'best_score' => 1420,
        'sessions_completed' => 12,
        'accuracy' => 86.0,
    ]);
    ProgressSnapshot::factory()->for($this->profile)->create([
        'skill_key' => SkillKey::Focus,
        'score_after' => 640,
        'delta' => 140,
        'recorded_at' => now()->subMinutes(10),
    ]);
    ProgressSnapshot::factory()->for($this->profile)->create([
        'skill_key' => SkillKey::Speed,
        'score_after' => 512,
        'delta' => -6,
        'recorded_at' => now()->subMinutes(5),
    ]);

    $achievement = Achievement::query()->where('slug', 'first-step')->firstOrFail();

    AchievementUnlock::factory()
        ->for($this->profile)
        ->for($achievement)
        ->create(['unlocked_at' => now()->subMinute()]);

    Native::visit('/progress')
        ->assertSee('days in rhythm')
        ->assertSee('5 days')
        ->assertSee('2 of the last 7 days')
        ->assertSee('Focus')
        ->assertSee('640 / 1000')
        ->assertSee('+140')
        ->assertSee('Speed')
        ->assertSee('-6')
        ->assertSee('Workouts')
        ->assertSee('8')
        ->assertSee('42 min')
        ->assertSee('83%')
        ->assertSee('1.4 s')
        ->assertSee('x6')
        ->assertSee('Personal bests')
        ->assertSee('Signal Shift')
        ->assertSee('1420')
        ->assertSee('12 sessions')
        ->assertSee('86% accuracy')
        ->assertSee('1 OF 6 UNLOCKED')
        ->assertSee('Unlocked Jul 18, 2026')
        ->assertDontSee('No training evidence yet')
        ->assertDontSee('Ready for day one')
        ->assertAccessible();
});

test('an incomplete profile is returned to onboarding before progress loads', function () {
    $this->profile->update(['onboarding_completed_at' => null]);

    Native::visit('/progress')
        ->assertReplacedWith('/onboarding');
});

test('reduced motion removes authored progress durations', function () {
    Setting::query()
        ->whereBelongsTo($this->profile)
        ->update(['reduced_motion' => true]);

    Native::visit('/progress')
        ->assertSet('reducedMotion', true)
        ->assertSet('motionDuration', 0)
        ->assertAccessible();
});

test('sub-minute and hour-scale training time formats stay honest', function () {
    Statistic::factory()->for($this->profile)->create([
        'scope_key' => 'overall',
        'workouts_completed' => 1,
        'sessions_completed' => 2,
        'training_seconds' => 4980,
        'accuracy' => null,
        'average_response_ms' => 640,
        'longest_combo' => 0,
    ]);

    Native::visit('/progress')
        ->assertSee('1 h 23 min')
        ->assertSee('Not measured')
        ->assertSee('640 ms')
        ->assertSee('None yet')
        ->assertAccessible();
});
