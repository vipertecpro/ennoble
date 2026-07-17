<?php

use App\Domain\Workout\WorkoutService;
use App\Enums\Difficulty;
use App\Enums\SkillKey;
use App\Enums\WorkoutStatus;
use App\Models\Achievement;
use App\Models\AchievementUnlock;
use App\Models\DailyWorkout;
use App\Models\Game;
use App\Models\GameLevel;
use App\Models\Profile as LocalProfile;
use App\Models\ProgressSnapshot;
use App\Models\Setting;
use App\Models\Statistic;
use App\NativeComponents\Screens\WorkoutIntroduction;
use Carbon\CarbonImmutable;
use Database\Seeders\GameLevelSeeder;
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

test('a first-time dashboard renders an intentional pending workout and honest empty previews', function () {
    Native::visit('/')
        ->assertSee('Good Morning')
        ->assertSee('Ada.')
        ->assertSee('Today’s Workout')
        ->assertSee('Daily Momentum')
        ->assertSee('About 7 min')
        ->assertSee('Intermediate')
        ->assertSee('Focus · Speed · Precision · Adaptability')
        ->assertSee('Start Training')
        ->assertSee('No streak yet')
        ->assertSee('No skill progress yet')
        ->assertSee('0 of 7 days')
        ->assertSee('No workout history yet')
        ->assertSee('No achievements yet')
        ->assertSee('Memory Path')
        ->assertSee('Pattern Pulse')
        ->assertSee('Word Forge')
        ->assertSee('Quick Read')
        ->assertAccessible();

    $workout = DailyWorkout::query()->whereBelongsTo($this->profile)->firstOrFail();

    expect($workout->status)->toBe(WorkoutStatus::Pending)
        ->and($workout->items)->toHaveCount(2);
});

test('the dashboard uses a friendly name when the optional local display name is empty', function () {
    $this->profile->update(['display_name' => '   ']);

    Native::visit('/')
        ->assertSee('Good Morning')
        ->assertSee('friend.')
        ->assertSet('displayName', 'friend')
        ->assertAccessible();
});

test('a returning user sees persisted streak progress personal best and latest achievement previews', function () {
    DailyWorkout::factory()->for($this->profile)->completed()->create([
        'workout_date' => today()->subDay(),
    ]);
    Statistic::factory()->for($this->profile)->create([
        'current_streak' => 3,
        'longest_streak' => 5,
        'workouts_completed' => 3,
    ]);

    $signalShift = Game::query()->where('slug', 'signal-shift')->firstOrFail();

    Statistic::factory()->for($this->profile)->for($signalShift)->create([
        'scope_key' => 'game:signal_shift',
        'best_score' => 1420,
    ]);
    ProgressSnapshot::factory()->for($this->profile)->create([
        'skill_key' => SkillKey::Focus,
        'score_after' => 640,
        'delta' => 140,
    ]);

    $achievement = Achievement::query()->where('slug', 'first-step')->firstOrFail();

    AchievementUnlock::factory()
        ->for($this->profile)
        ->for($achievement)
        ->create(['unlocked_at' => now()->subMinute()]);

    Native::visit('/')
        ->assertSee('Welcome back. Your next focused step is ready.')
        ->assertSee('3')
        ->assertSee('days in rhythm')
        ->assertSee('5 days')
        ->assertSee('Focus')
        ->assertSee('640 / 1000')
        ->assertSee('1 of 7 days')
        ->assertSee('1420')
        ->assertSee('Signal Shift')
        ->assertSee('First Step')
        ->assertSee('Complete your first daily workout.')
        ->assertAccessible();
});

test('an in-progress workout presents continue state and persisted item completion', function () {
    $workout = app(WorkoutService::class)->generateToday($this->profile);
    $workout->update([
        'status' => WorkoutStatus::InProgress,
        'started_at' => now()->subMinute(),
    ]);
    $workout->items->firstOrFail()->update([
        'status' => WorkoutStatus::Completed,
        'started_at' => now()->subMinutes(2),
        'completed_at' => now()->subMinute(),
    ]);

    Native::visit('/')
        ->assertSee('Continue Training')
        ->assertSee('50%')
        ->assertSet('workoutCompletionPercentage', 50)
        ->assertSet('workoutStatus', WorkoutStatus::InProgress->value);
});

test('a completed workout presents a non-interactive success state', function () {
    $workout = app(WorkoutService::class)->generateToday($this->profile);
    $workout->items()->update([
        'status' => WorkoutStatus::Completed,
        'started_at' => now()->subMinutes(5),
        'completed_at' => now(),
    ]);
    $workout->update([
        'status' => WorkoutStatus::Completed,
        'started_at' => now()->subMinutes(5),
        'completed_at' => now(),
    ]);

    Native::visit('/')
        ->assertSee('Completed Today')
        ->assertSee('100%')
        ->assertSet('workoutCompletionPercentage', 100)
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Completed Today'
            && ($node['props']['disabled'] ?? false) === true)
        ->assertAccessible();
});

test('the primary workout action navigates with subtle preference-aware feedback', function () {
    $bridge = Native::fakeBridge()
        ->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/')
        ->tap('Start Training')
        ->assertNavigatedTo('/workout')
        ->follow()
        ->assertScreen(WorkoutIntroduction::class)
        ->assertSee('A focused sequence for today')
        ->assertSee('Begin Workout')
        ->assertTabBarHidden()
        ->assertAccessible();

    expect($bridge->callsTo('Device.Vibrate'))->toHaveCount(1);
});

test('coming soon experiences open information without creating navigation', function () {
    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);

    Native::visit('/')
        ->tap('Memory Path')
        ->assertSet('comingSoonTitle', 'Memory Path')
        ->assertSet('bottomSheetVisible', true)
        ->assertSee('recalling ordered visual journeys')
        ->assertSee('informational only')
        ->assertNoNavigation()
        ->assertAccessible()
        ->tap('Got it')
        ->assertSet('bottomSheetVisible', false);
});

test('dashboard and section loading states do not replace unrelated content', function () {
    Native::visit('/')
        ->set('isWorkoutLoading', true)
        ->set('isStatisticsLoading', true)
        ->set('isProgressLoading', true)
        ->set('isAchievementLoading', true)
        ->assertSee('Preparing today’s workout')
        ->assertSee('Loading streak and personal bests')
        ->assertSee('Loading skill progress')
        ->assertSee('Loading latest achievement')
        ->assertSee('Good Morning')
        ->assertAccessible()
        ->set('dashboardState', 'loading')
        ->assertSee('Loading your Ennoble dashboard');
});

test('a missing local workout definition is recoverable without blocking other previews', function () {
    GameLevel::query()->delete();

    $dashboard = Native::visit('/')
        ->assertSee('Today’s workout could not be prepared')
        ->assertSee('Current Streak')
        ->assertSee('Progress Snapshot')
        ->assertSee('Recent Achievement')
        ->assertAccessible();

    (new GameLevelSeeder)->run();

    $dashboard
        ->tap('Retry workout')
        ->assertSee('Start Training')
        ->assertSet('workoutError', null);
});

test('section error states remain recoverable and accessible without blocking the dashboard', function () {
    Native::visit('/')
        ->set('statisticsError', 'Statistics preview unavailable.')
        ->set('progressError', 'Progress preview unavailable.')
        ->set('achievementError', 'Achievement preview unavailable.')
        ->assertSee('Statistics preview unavailable.')
        ->assertSee('Progress preview unavailable.')
        ->assertSee('Achievement preview unavailable.')
        ->assertSee('Today’s Workout')
        ->assertSee('On the Horizon')
        ->assertAccessible();
});

test('a repeated workout preparation failure uses the existing semantic toast service', function () {
    GameLevel::query()->delete();
    $bridge = Native::fakeBridge();

    Native::visit('/')
        ->tap('Retry workout')
        ->assertSee('Today’s workout could not be prepared');

    expect($bridge->callsTo('Dialog.Toast'))->toHaveCount(1)
        ->and($bridge->callsTo('Dialog.Toast')[0]['params']['message'])
        ->toBe('Error: Today’s workout is still unavailable.');
});

test('reduced motion removes authored durations and press transforms', function () {
    $this->profile->setting->update(['reduced_motion' => true]);

    Native::visit('/')
        ->assertSet('reducedMotion', true)
        ->assertSet('motionDuration', 0)
        ->assertSet('pressScale', 1.0)
        ->assertSet('pressOpacity', 1.0)
        ->assertAccessible();
});

test('adaptive preference starts from deterministic intermediate levels without changing the profile preference', function () {
    $this->profile->update(['difficulty_preference' => Difficulty::Adaptive]);

    Native::visit('/')
        ->assertSee('Adaptive')
        ->assertSee('Start Training')
        ->assertSet('workoutDifficulty', 'Adaptive');

    $workout = DailyWorkout::query()
        ->whereBelongsTo($this->profile)
        ->with('items.level')
        ->firstOrFail();

    expect($workout->items)
        ->toHaveCount(2)
        ->each(fn ($item) => $item->level->difficulty->toBe(Difficulty::Intermediate))
        ->and($this->profile->refresh()->difficulty_preference)->toBe(Difficulty::Adaptive);
});
