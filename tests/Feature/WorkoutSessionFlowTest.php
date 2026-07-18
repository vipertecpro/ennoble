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
use App\NativeComponents\Screens\SignalShiftGame;
use App\NativeComponents\Screens\WorkoutGameContainer;
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
        ->assertNavTitle('Today’s Workout')
        ->assertSee('A focused sequence for today')
        ->assertSee('About 7 min')
        ->assertSee('Intermediate')
        ->assertSee('Signal Shift')
        ->assertSee('Clear Thought')
        ->assertSee('Focus · Speed · Precision · Adaptability')
        ->assertSee('Signal Shift records real local gameplay evidence.')
        ->assertSee('Clear Thought remains an explicit framework-only step')
        ->assertSee('Begin Workout')
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
        ->tap('Start Now')
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
        ->tap('Start Now')
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
    $workout = app(WorkoutService::class)->generateToday($this->profile);
    $clearThoughtItem = $workout->items->last();
    $session = app(GameSessionService::class)->startPlaceholder($this->profile, $clearThoughtItem);

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
