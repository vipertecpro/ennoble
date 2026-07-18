<?php

use App\Domain\Games\GameSessionService;
use App\Domain\Games\SignalShift\SignalShiftGameService;
use App\Domain\Workout\WorkoutService;
use App\Enums\Difficulty;
use App\Enums\SessionStatus;
use App\Enums\WorkoutStatus;
use App\Models\DailyWorkout;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\NativeComponents\Screens\Home;
use App\NativeComponents\Screens\SignalShiftGame;
use App\NativeComponents\Screens\WorkoutComplete;
use App\NativeComponents\Screens\WorkoutIntroduction;
use App\NativeComponents\Screens\WorkoutPreparation;
use App\NativeComponents\Screens\WorkoutTransition;
use Carbon\CarbonImmutable;
use Native\Mobile\Edge\Transition;
use Native\Mobile\Testing\Native;
use Native\Mobile\Testing\TestableComponent;

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

function enterWorkoutSignalShiftRound(TestableComponent $game): TestableComponent
{
    $game->tap('Ready');

    while ($game->get('phase') === 'round_countdown') {
        $game->firePoll('tickGame');
    }

    return $game->assertSet('phase', 'playing');
}

test('the introduction presents the complete local workout before creating a session', function () {
    Native::visit('/workout')
        ->assertScreen(WorkoutIntroduction::class)
        ->assertNavBarHidden()
        ->assertSee('Ready your mind.')
        ->assertSee('WORKOUT RHYTHM')
        ->assertSee('About 7 min')
        ->assertSee('Intermediate')
        ->assertSee('Signal Shift')
        ->assertSee('Clear Thought')
        ->assertSee('Focus · Speed · Precision · Adaptability')
        ->assertSee('Guided practice preview · About 3 min')
        ->assertSee('Start settled.')
        ->assertSee('Begin Workout')
        ->assertMissingElement('progress_bar')
        ->assertMissingElement('bottom_nav')
        ->assertAccessible();

    expect(GameSession::query()->whereBelongsTo($this->profile)->count())->toBe(0);
});

test('the workout framework routes Signal Shift into its real native runner', function () {
    $introduction = Native::visit('/workout')
        ->tap('Begin Workout');

    $firstSession = GameSession::query()
        ->whereBelongsTo($this->profile)
        ->firstOrFail();
    $firstPreparationPath = '/workout/preparation/'.$firstSession->getKey();

    $preparation = $introduction
        ->assertReplacedWith($firstPreparationPath)
        ->assertTransition(Transition::FadeFromBottom)
        ->follow()
        ->assertScreen(WorkoutPreparation::class)
        ->assertSee('Game 1 of 2')
        ->assertSee('Settle into focus.')
        ->assertSee('Accuracy first. Speed follows.')
        ->assertSee('Enter Signal Shift')
        ->assertSet('countdown', 3)
        ->assertAccessible()
        ->firePoll('advanceCountdown')
        ->assertSet('countdown', 2)
        ->firePoll('advanceCountdown')
        ->assertSet('countdown', 1)
        ->firePoll('advanceCountdown')
        ->assertReplacedWith('/workout/game/signal-shift/'.$firstSession->getKey());

    $game = $preparation
        ->follow()
        ->assertScreen(SignalShiftGame::class)
        ->assertNavBarHidden()
        ->assertSee('Follow the rule. Ignore the noise.')
        ->assertSee('Learn the Signal')
        ->assertAccessible();

    $firstSession->refresh();

    expect($firstSession->isFrameworkPlaceholder())->toBeFalse()
        ->and($firstSession->status)->toBe(SessionStatus::InProgress)
        ->and(data_get($firstSession->state_snapshot, 'game'))->toBe('signal_shift')
        ->and($firstSession->rounds)->toHaveCount(0);
});

test('pause exit confirmation and resume preserve the local checkpoint', function () {
    $introduction = Native::visit('/workout')->tap('Begin Workout');
    $session = GameSession::query()->whereBelongsTo($this->profile)->firstOrFail();

    $game = $introduction
        ->follow()
        ->tap('Enter Signal Shift')
        ->follow()
        ->tap('Learn the Signal')
        ->tap('Skip Practice');
    enterWorkoutSignalShiftRound($game)
        ->firePoll('tickGame')
        ->tap('Pause')
        ->tap('Exit')
        ->assertSet('dialogVisible', true)
        ->assertSee('Leave workout?')
        ->assertElement('modal', fn (array $node): bool => ! array_key_exists('a11y_label', $node['props'] ?? []))
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Keep Training')
        ->assertElement('button', fn (array $node): bool => ($node['props']['label'] ?? null) === 'Exit to Home')
        ->tap('Keep Training')
        ->assertSet('dialogVisible', false)
        ->assertSet('paused', false)
        ->tap('Pause')
        ->tap('Exit')
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
        ->assertReplacedWith('/workout/game/signal-shift/'.$session->getKey())
        ->follow()
        ->assertSet('elapsedSeconds', 1)
        ->assertSet('paused', true)
        ->assertSee('Paused')
        ->tap('Resume')
        ->assertSet('paused', false)
        ->firePoll('tickGame')
        ->assertSet('elapsedSeconds', 2)
        ->assertAccessible();
});

test('restart clears only the unfinished Signal Shift attempt', function () {
    $introduction = Native::visit('/workout')->tap('Begin Workout');
    $workout = DailyWorkout::query()->whereBelongsTo($this->profile)->firstOrFail();
    $session = GameSession::query()->whereBelongsTo($this->profile)->firstOrFail();

    $game = $introduction
        ->follow()
        ->tap('Enter Signal Shift')
        ->follow()
        ->tap('Learn the Signal')
        ->tap('Skip Practice');
    enterWorkoutSignalShiftRound($game);
    $target = collect($game->get('stimuli'))->firstWhere('is_target', true);

    $game->tap($target['id'])
        ->tap('Pause')
        ->tap('Restart')
        ->assertSet('phase', 'instructions')
        ->assertSet('score', 0)
        ->assertSet('lives', 3)
        ->assertNoNavigation()
        ->assertAccessible();

    $workout->refresh()->load('items');
    $session->refresh();

    expect($workout->status)->toBe(WorkoutStatus::InProgress)
        ->and($workout->started_at)->not->toBeNull()
        ->and($workout->summary)->toBeNull()
        ->and($workout->items->first()->status)->toBe(WorkoutStatus::InProgress)
        ->and($workout->items->last()->status)->toBe(WorkoutStatus::Pending)
        ->and($session->rounds)->toHaveCount(0)
        ->and($session->current_round)->toBe(0)
        ->and(GameSession::query()->whereBelongsTo($this->profile)->count())->toBe(1);
});

test('reduced motion removes authored motion and requires an intentional transition action', function () {
    $this->profile->setting->update(['reduced_motion' => true]);

    $workout = app(WorkoutService::class)->generateToday($this->profile);
    $signalItem = $workout->items->firstOrFail();
    $session = app(GameSessionService::class)->startForWorkoutItem($this->profile, $signalItem);
    app(SignalShiftGameService::class)->recordTap(
        session: $session,
        stimulus: [
            'id' => 'transition-target',
            'color' => 'teal',
            'shape' => 'circle',
            'size' => 'large',
            'moving' => false,
            'rotated' => false,
            'is_target' => true,
        ],
        responseMs: 500,
        combo: 1,
        gameRound: 1,
        wave: 1,
        stateSnapshot: ['prepared' => true],
    );
    app(SignalShiftGameService::class)->complete($session);

    $transition = Native::visit('/workout/transition/'.$signalItem->getKey())
        ->assertScreen(WorkoutTransition::class)
        ->assertSet('reducedMotion', true)
        ->assertSet('motionDuration', 0)
        ->assertSet('autoTransitionEnabled', false)
        ->assertSet('autoTransitionSeconds', 0)
        ->assertSee('Continue when you feel ready')
        ->assertAccessible()
        ->firePoll('advanceAutoTransition')
        ->assertNoNavigation()
        ->tap('Start next now');

    $nextSession = GameSession::query()
        ->whereBelongsTo($this->profile)
        ->whereKeyNot($session->getKey())
        ->firstOrFail();

    $transition
        ->assertReplacedWith('/workout/preparation/'.$nextSession->getKey())
        ->assertTransition(Transition::None);
});

test('resume preserves the between-game coaching moment until the next game starts', function () {
    $workout = app(WorkoutService::class)->generateToday($this->profile);
    $signalItem = $workout->items->firstOrFail();
    $signalSession = GameSession::factory()
        ->for($this->profile)
        ->for($signalItem->game)
        ->for($signalItem->level, 'level')
        ->for($signalItem, 'workoutItem')
        ->completed()
        ->create();

    $signalItem->update([
        'status' => WorkoutStatus::Completed,
        'started_at' => $signalSession->started_at,
        'completed_at' => $signalSession->completed_at,
    ]);
    $workout->update([
        'status' => WorkoutStatus::InProgress,
        'started_at' => $signalSession->started_at,
    ]);

    Native::visit('/workout')
        ->assertSee('Resume Workout')
        ->tap('Resume Workout')
        ->assertReplacedWith('/workout/transition/'.$signalItem->getKey())
        ->assertTransition(Transition::FadeFromBottom)
        ->follow()
        ->assertScreen(WorkoutTransition::class)
        ->assertSee('Signal Shift complete')
        ->assertSee('UP NEXT')
        ->assertSee('Clear Thought')
        ->assertAccessible();
});

test('the final game celebrates before meaningful progress and returns completion to Home', function () {
    $workout = app(WorkoutService::class)->generateToday($this->profile);
    $signalItem = $workout->items->firstOrFail();
    $clearThoughtItem = $workout->items->last();

    GameSession::factory()
        ->for($this->profile)
        ->for($signalItem->game)
        ->for($signalItem->level, 'level')
        ->for($signalItem, 'workoutItem')
        ->completed()
        ->create([
            'score' => 640,
            'accuracy' => 87.5,
            'best_combo' => 4,
        ]);
    $signalItem->update([
        'status' => WorkoutStatus::Completed,
        'started_at' => now()->subMinutes(4),
        'completed_at' => now()->subMinutes(2),
    ]);
    GameSession::factory()
        ->for($this->profile)
        ->for($clearThoughtItem->game)
        ->for($clearThoughtItem->level, 'level')
        ->for($clearThoughtItem, 'workoutItem')
        ->completed()
        ->create([
            'score' => 420,
            'accuracy' => 75.0,
            'best_combo' => 0,
            'hint_count' => 1,
        ]);
    $clearThoughtItem->update([
        'status' => WorkoutStatus::Completed,
        'started_at' => now()->subMinutes(2),
        'completed_at' => now(),
    ]);

    $transition = Native::visit('/workout/transition/'.$clearThoughtItem->getKey())
        ->assertScreen(WorkoutTransition::class)
        ->assertSet('isFinalGame', true)
        ->assertSee('Clear Thought complete')
        ->assertSee('Great focus.')
        ->assertSee('75% accuracy · 420 points')
        ->assertSee('Workout celebration')
        ->assertAccessible()
        ->tap('See results now');

    $workout->refresh();

    expect($workout->status)->toBe(WorkoutStatus::Completed);

    $completion = $transition
        ->assertReplacedWith('/workout/complete/'.$workout->getKey())
        ->follow()
        ->assertScreen(WorkoutComplete::class)
        ->assertSet('phase', 'celebration')
        ->assertSee('Workout complete')
        ->assertSee('First benchmark set')
        ->assertSee('See today’s progress')
        ->assertAccessible()
        ->tap('See today’s progress')
        ->assertSet('phase', 'progress')
        ->assertSee('Today’s progress')
        ->assertSee('BEST MOMENT')
        ->assertSee('STREAK')
        ->assertSee('Return home')
        ->tap('Return home');

    $completion
        ->assertReplacedWith('/')
        ->follow()
        ->assertScreen(Home::class)
        ->assertSet('celebrateWorkoutReturn', true)
        ->assertSee('WORKOUT COMPLETED')
        ->assertSee('Achievement unlocked · First Step')
        ->assertSee('Complete for today')
        ->assertAccessible();
});

