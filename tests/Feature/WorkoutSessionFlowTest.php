<?php

use App\Domain\Games\GameSessionService;
use App\Domain\Statistics\StatisticsService;
use App\Enums\Difficulty;
use App\Enums\SessionStatus;
use App\Enums\WorkoutStatus;
use App\Models\AchievementUnlock;
use App\Models\DailyWorkout;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\ProgressSnapshot;
use App\Models\Setting;
use App\Models\Statistic;
use App\NativeComponents\Screens\WorkoutComplete;
use App\NativeComponents\Screens\WorkoutGameContainer;
use App\NativeComponents\Screens\WorkoutIntroduction;
use App\NativeComponents\Screens\WorkoutPreparation;
use App\NativeComponents\Screens\WorkoutTransition;
use Carbon\CarbonImmutable;
use Native\Mobile\Edge\Transition;
use Native\Mobile\Testing\Native;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-07-18 10:30:00');

    $this->profile = Profile::factory()->onboarded()->create([
        'display_name' => 'Ada',
        'difficulty_preference' => Difficulty::Intermediate,
    ]);
    Setting::factory()->for($this->profile)->create([
        'haptics_enabled' => true,
        'reduced_motion' => false,
    ]);

    Native::fakeBridge()->respondTo('Device.Vibrate', ['success' => true]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('the introduction presents the complete local workout before creating a session', function () {
    Native::visit('/workout')
        ->assertScreen(WorkoutIntroduction::class)
        ->assertNavTitle('Today’s Workout')
        ->assertSee('A focused sequence for today')
        ->assertSee('About 7 min')
        ->assertSee('Intermediate')
        ->assertSee('Signal Shift')
        ->assertSee('Clear Thought')
        ->assertSee('Focus · Speed · Precision · Adaptability')
        ->assertSee('No gameplay data is invented.')
        ->assertSee('Begin Workout')
        ->assertTabBarHidden()
        ->assertAccessible();

    expect(GameSession::query()->whereBelongsTo($this->profile)->count())->toBe(0);
});

test('the placeholder workout completes every native phase without creating gameplay evidence', function () {
    $introduction = Native::visit('/workout')
        ->tap('Begin Workout');

    $firstSession = GameSession::query()
        ->whereBelongsTo($this->profile)
        ->firstOrFail();
    $firstPreparationPath = '/workout/preparation/'.$firstSession->getKey();

    $preparation = $introduction
        ->assertReplacedWith($firstPreparationPath)
        ->assertTransition(Transition::Fade)
        ->follow()
        ->assertScreen(WorkoutPreparation::class)
        ->assertSee('Game 1 of 2')
        ->assertSee('HOW TO APPROACH THIS GAME')
        ->assertSee('Breathe and get ready')
        ->assertSet('countdown', 3)
        ->assertAccessible()
        ->firePoll('advanceCountdown')
        ->assertSet('countdown', 2)
        ->firePoll('advanceCountdown')
        ->assertSet('countdown', 1)
        ->firePoll('advanceCountdown')
        ->assertReplacedWith('/workout/game/'.$firstSession->getKey());

    $game = $preparation
        ->follow()
        ->assertScreen(WorkoutGameContainer::class)
        ->assertSee('FRAMEWORK PLACEHOLDER')
        ->assertSee('No answers, score, accuracy, personal best, or skill progress will be created.')
        ->assertSee('0:00')
        ->assertAccessible()
        ->firePoll('tickTimer')
        ->firePoll('tickTimer')
        ->assertSet('elapsedSeconds', 2)
        ->tap('Pause')
        ->assertSet('paused', true)
        ->assertSet('bottomSheetVisible', true)
        ->assertSee('Workout paused')
        ->assertElement('bottom_sheet', fn (array $node): bool => ! array_key_exists('a11y_label', $node['props'] ?? []))
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Resume')
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Restart Workout')
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Exit Workout')
        ->firePoll('tickTimer')
        ->assertSet('elapsedSeconds', 2)
        ->tap('Resume')
        ->assertSet('paused', false)
        ->tap('Complete Placeholder');

    $firstItemId = $firstSession->daily_workout_item_id;
    $transition = $game
        ->assertReplacedWith('/workout/transition/'.$firstItemId)
        ->follow()
        ->assertScreen(WorkoutTransition::class)
        ->assertSee('No gameplay score was recorded in this framework placeholder.')
        ->assertSee('UP NEXT')
        ->assertSet('autoTransitionSeconds', 3)
        ->assertAccessible()
        ->firePoll('advanceAutoTransition')
        ->firePoll('advanceAutoTransition')
        ->firePoll('advanceAutoTransition');

    $secondSession = GameSession::query()
        ->whereBelongsTo($this->profile)
        ->whereKeyNot($firstSession->getKey())
        ->firstOrFail();

    $secondPreparation = $transition
        ->assertReplacedWith('/workout/preparation/'.$secondSession->getKey())
        ->follow()
        ->assertScreen(WorkoutPreparation::class)
        ->assertSee('Game 2 of 2')
        ->tap('Start Now')
        ->assertReplacedWith('/workout/game/'.$secondSession->getKey());

    $workout = DailyWorkout::query()
        ->whereBelongsTo($this->profile)
        ->firstOrFail();

    $completion = $secondPreparation
        ->follow()
        ->assertScreen(WorkoutGameContainer::class)
        ->firePoll('tickTimer')
        ->tap('Complete Placeholder')
        ->assertReplacedWith('/workout/complete/'.$workout->getKey())
        ->follow()
        ->assertScreen(WorkoutComplete::class)
        ->assertNavTitle('Workout Complete')
        ->assertSee('Workout complete')
        ->assertSee('3 sec')
        ->assertSee('2')
        ->assertSee('Skill progress was not recorded because gameplay is intentionally deferred.')
        ->assertSee('Not recorded')
        ->assertAccessible();

    $workout->refresh();
    $sessions = GameSession::query()
        ->whereBelongsTo($this->profile)
        ->orderBy('id')
        ->get();

    expect($workout->status)->toBe(WorkoutStatus::Completed)
        ->and($workout->training_seconds)->toBe(3)
        ->and($workout->accuracy)->toBeNull()
        ->and(data_get($workout->summary, 'has_gameplay_evidence'))->toBeFalse()
        ->and(data_get($workout->summary, 'score'))->toBeNull()
        ->and(data_get($workout->summary, 'accuracy'))->toBeNull()
        ->and($sessions)->toHaveCount(2)
        ->and($sessions->every->isFrameworkPlaceholder())->toBeTrue()
        ->and($sessions->every(fn (GameSession $session): bool => $session->status === SessionStatus::Completed))->toBeTrue()
        ->and($sessions->every(fn (GameSession $session): bool => $session->score === null && $session->accuracy === null))->toBeTrue()
        ->and($sessions->every(fn (GameSession $session): bool => $session->statistics_recorded_at === null))->toBeTrue()
        ->and(Statistic::query()->whereBelongsTo($this->profile)->count())->toBe(0)
        ->and(ProgressSnapshot::query()->whereBelongsTo($this->profile)->count())->toBe(0)
        ->and(AchievementUnlock::query()->whereBelongsTo($this->profile)->count())->toBe(0);

    $gamePreviews = app(StatisticsService::class)->gamePreviews($this->profile);
    $rebuiltStatistics = app(StatisticsService::class)->rebuild($this->profile);

    expect($gamePreviews->every(
        fn (array $preview): bool => $preview['session_count'] === 0
            && $preview['completion_count'] === 0
            && $preview['best_score'] === null,
    ))->toBeTrue()
        ->and($rebuiltStatistics)->toBeEmpty();

    $completion
        ->tap('Continue to Home')
        ->assertReplacedWith('/');
});

test('pause exit confirmation and resume preserve the local checkpoint', function () {
    $introduction = Native::visit('/workout')->tap('Begin Workout');
    $session = GameSession::query()->whereBelongsTo($this->profile)->firstOrFail();

    $game = $introduction
        ->follow()
        ->tap('Start Now')
        ->follow()
        ->firePoll('tickTimer')
        ->tap('Pause')
        ->tap('Exit Workout')
        ->assertSet('dialogVisible', true)
        ->assertSee('Leave workout?')
        ->assertElement('modal', fn (array $node): bool => ! array_key_exists('a11y_label', $node['props'] ?? []))
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Keep Training')
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Exit to Home')
        ->tap('Keep Training')
        ->assertSet('dialogVisible', false)
        ->assertSet('paused', false)
        ->tap('Pause')
        ->tap('Exit Workout')
        ->tap('Exit to Home')
        ->assertReplacedWith('/');

    $session->refresh();

    expect($session->status)->toBe(SessionStatus::InProgress)
        ->and(data_get($session->state_snapshot, 'prepared'))->toBeTrue()
        ->and(data_get($session->state_snapshot, 'paused'))->toBeTrue()
        ->and(data_get($session->state_snapshot, 'elapsed_seconds'))->toBe(1);

    Native::visit('/workout')
        ->assertSee('Resume Workout')
        ->tap('Resume Workout')
        ->assertReplacedWith('/workout/game/'.$session->getKey())
        ->follow()
        ->assertSet('elapsedSeconds', 1)
        ->assertSet('paused', true)
        ->assertSee('Workout paused')
        ->tap('Resume')
        ->assertSet('paused', false)
        ->firePoll('tickTimer')
        ->assertSet('elapsedSeconds', 2)
        ->assertAccessible();
});

test('restart clears only placeholder session state and returns to a fresh introduction', function () {
    $introduction = Native::visit('/workout')->tap('Begin Workout');
    $workout = DailyWorkout::query()->whereBelongsTo($this->profile)->firstOrFail();

    $introduction
        ->follow()
        ->tap('Start Now')
        ->follow()
        ->firePoll('tickTimer')
        ->tap('Pause')
        ->tap('Restart Workout')
        ->assertReplacedWith('/workout')
        ->follow()
        ->assertScreen(WorkoutIntroduction::class)
        ->assertSee('Begin Workout')
        ->assertAccessible();

    $workout->refresh()->load('items');

    expect($workout->status)->toBe(WorkoutStatus::Pending)
        ->and($workout->started_at)->toBeNull()
        ->and($workout->summary)->toBeNull()
        ->and($workout->items->every(fn ($item): bool => $item->status === WorkoutStatus::Pending))->toBeTrue()
        ->and(GameSession::query()->whereBelongsTo($this->profile)->count())->toBe(0);
});

test('reduced motion removes authored motion and requires an intentional transition action', function () {
    $this->profile->setting->update(['reduced_motion' => true]);

    $introduction = Native::visit('/workout')
        ->assertSet('reducedMotion', true)
        ->assertSet('motionDuration', 0)
        ->tap('Begin Workout')
        ->assertTransition(Transition::None);
    $session = GameSession::query()->whereBelongsTo($this->profile)->firstOrFail();

    $game = $introduction
        ->follow()
        ->assertSet('motionDuration', 0)
        ->tap('Start Now')
        ->assertTransition(Transition::None)
        ->follow()
        ->assertSet('motionDuration', 0)
        ->tap('Complete Placeholder')
        ->assertTransition(Transition::None);

    $transition = $game
        ->follow()
        ->assertScreen(WorkoutTransition::class)
        ->assertSet('reducedMotion', true)
        ->assertSet('motionDuration', 0)
        ->assertSet('autoTransitionEnabled', false)
        ->assertSet('autoTransitionSeconds', 0)
        ->assertSee('Automatic transition is off while Reduced Motion is enabled.')
        ->assertAccessible()
        ->firePoll('advanceAutoTransition')
        ->assertNoNavigation()
        ->tap('Continue');

    $nextSession = GameSession::query()
        ->whereBelongsTo($this->profile)
        ->whereKeyNot($session->getKey())
        ->firstOrFail();

    $transition
        ->assertReplacedWith('/workout/preparation/'.$nextSession->getKey())
        ->assertTransition(Transition::None);
});

test('placeholder sessions cannot enter the real scoring pipeline', function () {
    Native::visit('/workout')->tap('Begin Workout');
    $session = GameSession::query()->whereBelongsTo($this->profile)->firstOrFail();

    expect(fn () => app(GameSessionService::class)->complete($session))
        ->toThrow(LogicException::class, 'Framework placeholder sessions cannot create gameplay evidence.');
});

test('missing workout checkpoints present recoverable accessible states', function () {
    Native::visit('/workout/game/999999')
        ->assertScreen(WorkoutGameContainer::class)
        ->assertSet('screenState', 'error')
        ->assertSee('Game checkpoint unavailable')
        ->assertSee('Return home')
        ->assertAccessible()
        ->tap('Return home')
        ->assertReplacedWith('/');
});
