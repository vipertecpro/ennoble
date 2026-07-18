<?php

use App\Domain\Games\GameSessionService;
use App\Domain\Games\SignalShift\SignalShiftRuleEngine;
use App\Domain\Workout\WorkoutService;
use App\Enums\Difficulty;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Enums\WorkoutStatus;
use App\Models\AchievementUnlock;
use App\Models\DailyWorkout;
use App\Models\GameLevel;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\ProgressSnapshot;
use App\Models\Setting;
use App\Models\Statistic;
use App\NativeComponents\Screens\ClearThoughtGame;
use App\NativeComponents\Screens\SignalShiftGame;
use App\NativeComponents\Screens\WorkoutComplete;
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

function preparedSignalShiftSession(Profile $profile): GameSession
{
    $workout = app(WorkoutService::class)->generateToday($profile);
    $item = $workout->items->firstOrFail();
    $session = app(GameSessionService::class)->startForWorkoutItem($profile, $item);

    return app(GameSessionService::class)->checkpoint($session, ['prepared' => true]);
}

function startSignalShiftRound(TestableComponent $game): TestableComponent
{
    $game->tap('Ready')
        ->assertSet('phase', 'round_countdown')
        ->assertSet('roundCountdown', 3);

    while ($game->get('phase') === 'round_countdown') {
        $game->firePoll('tickGame');
    }

    return $game->assertSet('phase', 'playing');
}

function tapCurrentSignalTarget(TestableComponent $game): TestableComponent
{
    $target = collect($game->get('stimuli'))->firstWhere('is_target', true);

    expect($target)->not->toBeNull();

    return $game->tap($target['id']);
}

function finishSignalShiftPerfectly(TestableComponent $game): TestableComponent
{
    if ($game->get('phase') === 'instructions') {
        $game->tap($game->get('tutorialRequired') ? 'Learn the Signal' : 'Play Signal Shift');
    }

    if ($game->get('phase') === 'tutorial') {
        $game->tap('Skip Practice');
    }

    for ($round = 1; $round <= 3; $round++) {
        startSignalShiftRound($game);

        while ($game->get('phase') === 'playing') {
            tapCurrentSignalTarget($game);
        }

        expect($game->get('phase'))->toBe('round_result')
            ->and($game->get('gameRound'))->toBe($round);

        $game->tap($round < 3 ? 'Next Rule' : 'Reveal Results');
    }

    return $game;
}

test('all bundled difficulties expose three progressively scaled data driven rules', function () {
    $levels = GameLevel::query()
        ->whereHas('game', fn ($query) => $query->where('slug', 'signal-shift'))
        ->orderByRaw("case difficulty when 'beginner' then 1 when 'intermediate' then 2 else 3 end")
        ->get();
    $engine = app(SignalShiftRuleEngine::class);

    expect($levels)->toHaveCount(3)
        ->and($levels->pluck('difficulty')->all())->toBe([
            Difficulty::Beginner,
            Difficulty::Intermediate,
            Difficulty::Advanced,
        ]);

    foreach ($levels as $level) {
        $rules = $engine->rulesFor($level);

        expect($rules)->toHaveCount(3)
            ->and(data_get($level->configuration, 'content_version'))->toBe(2)
            ->and(data_get($level->configuration, 'lives'))->toBe(3)
            ->and(collect($rules)->every(
                fn ($rule): bool => $rule->spawnDensity >= 2
                    && $rule->waveCount >= 1
                    && $rule->secondsPerWave >= 1,
            ))->toBeTrue();
    }

    expect($engine->ruleFor($levels[0], 1)->secondsPerWave)
        ->toBeGreaterThan($engine->ruleFor($levels[2], 1)->secondsPerWave)
        ->and($engine->ruleFor($levels[0], 1)->spawnDensity)
        ->toBeLessThan($engine->ruleFor($levels[2], 1)->spawnDensity)
        ->and($engine->ruleFor($levels[0], 1)->speedModifier)
        ->toBeLessThan($engine->ruleFor($levels[2], 1)->speedModifier);
});

test('result formatting remains compatible with the bundled mobile PHP runtime', function () {
    $game = new SignalShiftGame;
    $formatAccuracy = new ReflectionMethod($game, 'formatAccuracy');
    $formatNumber = new ReflectionMethod($game, 'formatNumber');
    $source = file_get_contents(app_path('NativeComponents/Screens/SignalShiftGame.php'));

    expect($formatAccuracy->invoke($game, 87.25))->toBe('87.3%')
        ->and($formatNumber->invoke($game, 12345))->toBe('12,345')
        ->and($source)->not->toContain('Number::format');
});

test('first play teaches the rule without recording tutorial evidence', function () {
    $session = preparedSignalShiftSession($this->profile);
    $game = Native::visit('/workout/game/signal-shift/'.$session->getKey())
        ->assertScreen(SignalShiftGame::class)
        ->assertNavBarHidden()
        ->assertSet('phase', 'instructions')
        ->assertSet('tutorialRequired', true)
        ->assertSee('Follow the rule. Ignore the noise.')
        ->assertSee('Learn the Signal')
        ->assertAccessible()
        ->tap('Learn the Signal')
        ->assertSet('phase', 'tutorial')
        ->assertSee('PRACTICE · NO SCORE');
    $stimuli = collect($game->get('stimuli'));
    $distractor = $stimuli->firstWhere('is_target', false);
    $target = $stimuli->firstWhere('is_target', true);

    $game->tap($distractor['id'])
        ->assertSet('tutorialComplete', false)
        ->assertSee('Check both the color and the shape.')
        ->tap($target['id'])
        ->assertSet('tutorialComplete', true)
        ->assertSee('Shape and color both matched.')
        ->tap('Enter Round 1')
        ->assertSet('phase', 'round_intro')
        ->assertSet('gameRound', 1)
        ->assertSee('Lock onto the signal')
        ->assertAccessible();

    $game->tap('Ready')
        ->assertSet('phase', 'round_countdown')
        ->assertSet('roundCountdown', 3)
        ->assertSee($game->get('ruleText'))
        ->firePoll('tickGame')
        ->assertSet('roundCountdown', 2)
        ->firePoll('tickGame')
        ->assertSet('roundCountdown', 1)
        ->firePoll('tickGame')
        ->assertSet('roundCountdown', 0)
        ->assertSee('GO')
        ->firePoll('tickGame')
        ->assertSet('phase', 'playing')
        ->assertAccessible();

    $session->refresh();

    expect($session->rounds)->toHaveCount(0)
        ->and(data_get($session->state_snapshot, 'tutorial_complete'))->toBeTrue()
        ->and(data_get($session->state_snapshot, 'game_round'))->toBe(1);
});

test('correct taps complete three rounds and update local evidence systems', function () {
    $session = preparedSignalShiftSession($this->profile);
    $game = Native::visit('/workout/game/signal-shift/'.$session->getKey());

    finishSignalShiftPerfectly($game)
        ->assertSet('phase', 'game_result')
        ->assertSet('newPersonalBest', true)
        ->assertSee('SAVED PRIVATELY ON THIS DEVICE')
        ->assertSee('Signal mastered.')
        ->assertSee('100.0%')
        ->assertAccessible();

    $session->refresh();
    $signalStatistic = Statistic::query()
        ->whereBelongsTo($this->profile)
        ->where('scope_key', 'game:signal_shift')
        ->firstOrFail();

    expect($session->status)->toBe(SessionStatus::Completed)
        ->and($session->mode)->toBeNull()
        ->and($session->rounds)->toHaveCount(8)
        ->and($session->correct_count)->toBe(8)
        ->and($session->incorrect_count)->toBe(0)
        ->and($session->missed_count)->toBe(0)
        ->and($session->accuracy)->toBe(100.0)
        ->and($session->best_combo)->toBe(8)
        ->and($session->score)->toBeGreaterThanOrEqual(1500)
        ->and($session->statistics_recorded_at)->not->toBeNull()
        ->and($signalStatistic->sessions_completed)->toBe(1)
        ->and($signalStatistic->accuracy)->toBe(100.0)
        ->and($signalStatistic->best_score)->toBe($session->score)
        ->and(Statistic::query()->whereBelongsTo($this->profile)->count())->toBe(2)
        ->and(ProgressSnapshot::query()->whereBelongsTo($this->profile)->count())->toBe(4)
        ->and(AchievementUnlock::query()->whereBelongsTo($this->profile)->count())->toBe(3);

    $game->tap('Continue Workout')
        ->assertReplacedWith('/workout/transition/'.$session->daily_workout_item_id)
        ->assertTransition(Transition::Fade)
        ->follow()
        ->assertScreen(WorkoutTransition::class)
        ->assertSee(number_format($session->score).' points')
        ->assertSee('100% accuracy')
        ->assertSee('x8 focus chain')
        ->assertAccessible();
});

test('gameplay feedback is immediate rewarding and transient', function () {
    $this->profile->update(['difficulty_preference' => Difficulty::Advanced]);
    $session = preparedSignalShiftSession($this->profile);
    $game = Native::visit('/workout/game/signal-shift/'.$session->getKey())
        ->tap('Learn the Signal')
        ->tap('Skip Practice');
    startSignalShiftRound($game);

    tapCurrentSignalTarget($game)
        ->assertSet('feedbackTone', 'success')
        ->assertSet('feedbackSerial', 1);

    expect($game->get('floatingScore'))->toStartWith('+')
        ->and($game->get('combo'))->toBe(1);

    tapCurrentSignalTarget($game)
        ->assertSet('feedbackTone', 'success')
        ->assertSet('feedbackSerial', 2)
        ->assertSet('combo', 2)
        ->assertSee('x2')
        ->assertAccessible()
        ->firePoll('tickGame')
        ->assertSet('feedbackTone', 'neutral')
        ->assertSet('floatingScore', '')
        ->assertDontSee('x2');
});

test('wrong taps reset combo cost lives and failure can restart cleanly', function () {
    $session = preparedSignalShiftSession($this->profile);
    $game = Native::visit('/workout/game/signal-shift/'.$session->getKey())
        ->tap('Learn the Signal')
        ->tap('Skip Practice');
    startSignalShiftRound($game);
    $distractors = collect($game->get('stimuli'))
        ->where('is_target', false)
        ->values();

    foreach ($distractors->take(3) as $index => $distractor) {
        $game->tap($distractor['id'])
            ->assertSet('lives', 2 - $index)
            ->assertSet('combo', 0);
    }

    $game->assertSet('phase', 'failed')
        ->assertSee('Reset. Read. React.')
        ->assertSee('Play Again')
        ->assertAccessible()
        ->tap('Play Again')
        ->assertSet('phase', 'instructions')
        ->assertSet('lives', 3)
        ->assertSet('score', 0);

    $session->refresh();

    expect($session->status)->toBe(SessionStatus::InProgress)
        ->and($session->rounds)->toHaveCount(0)
        ->and($session->current_round)->toBe(0)
        ->and($session->incorrect_count)->toBe(0);
});

test('an expired wave records a miss and advances with a lost life', function () {
    $session = preparedSignalShiftSession($this->profile);
    $game = Native::visit('/workout/game/signal-shift/'.$session->getKey())
        ->tap('Learn the Signal')
        ->tap('Skip Practice');
    startSignalShiftRound($game);
    $startingWaveSeconds = $game->get('waveSecondsRemaining');

    for ($second = 0; $second < $startingWaveSeconds; $second++) {
        $game->firePoll('tickGame');
    }

    $game->assertSet('phase', 'playing')
        ->assertSet('wave', 2)
        ->assertSet('lives', 2)
        ->assertSet('combo', 0)
        ->assertSee('Target missed')
        ->assertAccessible();

    $round = $session->rounds()->firstOrFail();

    expect($round->outcome)->toBe(RoundOutcome::Missed)
        ->and(data_get($round->response, 'game_round'))->toBe(1)
        ->and(data_get($round->response, 'wave'))->toBe(1)
        ->and($session->fresh()->missed_count)->toBe(1);
});

test('pause exit and resume preserve the active rule and countdown checkpoint', function () {
    $session = preparedSignalShiftSession($this->profile);
    $game = Native::visit('/workout/game/signal-shift/'.$session->getKey())
        ->tap('Learn the Signal')
        ->tap('Skip Practice');
    startSignalShiftRound($game)->firePoll('tickGame');
    $remaining = $game->get('waveSecondsRemaining');
    $rule = $game->get('ruleText');

    $game->tap('Pause')
        ->assertSet('paused', true)
        ->assertSet('bottomSheetVisible', true)
        ->assertSee('Paused')
        ->firePoll('tickGame')
        ->assertSet('waveSecondsRemaining', $remaining)
        ->tap('Exit')
        ->assertSet('dialogVisible', true)
        ->assertSee('Leave workout?')
        ->tap('Exit to Home')
        ->assertReplacedWith('/');

    $session->refresh();

    expect(data_get($session->state_snapshot, 'paused'))->toBeTrue()
        ->and(data_get($session->state_snapshot, 'rule_text'))->toBe($rule)
        ->and(data_get($session->state_snapshot, 'wave_seconds_remaining'))->toBe($remaining);

    Native::visit('/workout')
        ->assertSee('Resume Workout')
        ->tap('Resume Workout')
        ->assertReplacedWith('/workout/game/signal-shift/'.$session->getKey())
        ->follow()
        ->assertScreen(SignalShiftGame::class)
        ->assertSet('paused', true)
        ->assertSet('waveSecondsRemaining', $remaining)
        ->assertSee($rule)
        ->tap('Resume')
        ->assertSet('paused', false)
        ->firePoll('tickGame')
        ->assertSet('waveSecondsRemaining', $remaining - 1)
        ->assertAccessible();
});

test('reduced motion removes stimulus translation while preserving gameplay', function () {
    $this->profile->setting->update(['reduced_motion' => true]);
    $session = preparedSignalShiftSession($this->profile);
    $game = Native::visit('/workout/game/signal-shift/'.$session->getKey())
        ->assertSet('reducedMotion', true)
        ->assertSet('motionDuration', 0)
        ->assertSet('countdownMotionDuration', 0)
        ->assertSet('feedbackMotionDuration', 0)
        ->tap('Learn the Signal')
        ->tap('Skip Practice');
    startSignalShiftRound($game);

    expect(collect($game->get('stimuli'))->every(
        fn (array $stimulus): bool => $stimulus['translate_x'] === 0
            && $stimulus['translate_y'] === 0
            && $stimulus['motion_duration'] === 0,
    ))->toBeTrue();

    tapCurrentSignalTarget($game)
        ->assertSet('wave', 2)
        ->assertSet('lives', 3)
        ->assertAccessible();
});

test('completed Signal Shift sessions make later tutorials optional', function () {
    $firstSession = preparedSignalShiftSession($this->profile);
    finishSignalShiftPerfectly(
        Native::visit('/workout/game/signal-shift/'.$firstSession->getKey()),
    );

    CarbonImmutable::setTestNow('2026-07-19 10:30:00');
    $secondSession = preparedSignalShiftSession($this->profile);

    Native::visit('/workout/game/signal-shift/'.$secondSession->getKey())
        ->assertSet('tutorialRequired', false)
        ->assertSee('Play Signal Shift')
        ->assertSee('Practice Tutorial')
        ->tap('Practice Tutorial')
        ->assertSet('phase', 'tutorial')
        ->assertAccessible();
});

test('the full workout completes with real Signal Shift and Clear Thought evidence', function () {
    $session = preparedSignalShiftSession($this->profile);
    $signalGame = finishSignalShiftPerfectly(
        Native::visit('/workout/game/signal-shift/'.$session->getKey()),
    );
    $transition = $signalGame
        ->tap('Continue Workout')
        ->follow()
        ->tap('Start next now');
    $clearSession = GameSession::query()
        ->whereBelongsTo($this->profile)
        ->whereKeyNot($session->getKey())
        ->firstOrFail();

    expect($clearSession->isFrameworkPlaceholder())->toBeFalse();

    $preparation = $transition
        ->assertReplacedWith('/workout/preparation/'.$clearSession->getKey())
        ->follow()
        ->assertScreen(WorkoutPreparation::class)
        ->assertSee('Game 2 of 2')
        ->tap('Enter Clear Thought')
        ->assertReplacedWith('/workout/game/clear-thought/'.$clearSession->getKey());
    $workout = DailyWorkout::query()->whereBelongsTo($this->profile)->firstOrFail();
    $clearGame = finishClearThoughtPerfectly(
        $preparation->follow()->assertScreen(ClearThoughtGame::class),
    );
    $finalTransition = $clearGame
        ->assertSee('Clarity held.')
        ->tap('Continue Workout')
        ->assertReplacedWith('/workout/transition/'.$clearSession->daily_workout_item_id)
        ->follow()
        ->assertScreen(WorkoutTransition::class)
        ->assertSee('Excellent control.')
        ->assertSee('100% accuracy')
        ->assertDontSee('no score recorded')
        ->assertSee('Workout celebration')
        ->tap('See results now');

    $completion = $finalTransition
        ->assertReplacedWith('/workout/complete/'.$workout->getKey())
        ->follow()
        ->assertScreen(WorkoutComplete::class)
        ->assertSee('Workout complete.')
        ->assertSee('Excellent control today.')
        ->assertSee('See today’s progress')
        ->assertAccessible();

    $workout->refresh();
    $clearSession->refresh();

    expect($workout->status)->toBe(WorkoutStatus::Completed)
        ->and(data_get($workout->summary, 'has_gameplay_evidence'))->toBeTrue()
        ->and(data_get($workout->summary, 'sessions_completed'))->toBe(2)
        ->and(data_get($workout->summary, 'score'))
        ->toBe($session->fresh()->score + $clearSession->score)
        ->and(data_get($workout->summary, 'accuracy'))->toEqual(100.0)
        ->and($clearSession->score)->toBeGreaterThan(0)
        ->and($clearSession->statistics_recorded_at)->not->toBeNull()
        ->and(Statistic::query()
            ->whereBelongsTo($this->profile)
            ->where('scope_key', 'game:clear_thought')
            ->exists())->toBeTrue()
        ->and(AchievementUnlock::query()
            ->whereBelongsTo($this->profile)
            ->whereHas('achievement', fn ($query) => $query->where('slug', 'first-step'))
            ->exists())->toBeTrue();

    $completion
        ->tap('See today’s progress')
        ->assertSee('Today’s progress')
        ->assertSee('BEST MOMENT')
        ->tap('Return home')
        ->assertReplacedWith('/');
});

test('invalid Signal Shift checkpoints present an accessible recovery state', function () {
    Native::visit('/workout/game/signal-shift/999999')
        ->assertScreen(SignalShiftGame::class)
        ->assertSet('screenState', 'error')
        ->assertSee('Signal Shift unavailable')
        ->assertSee('Return to workout')
        ->assertAccessible()
        ->tap('Return to workout')
        ->assertReplacedWith('/workout');
});
