<?php

use App\Domain\Games\GameSessionService;
use App\Domain\Workout\WorkoutService;
use App\Enums\ClearThoughtMode;
use App\Enums\Difficulty;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Models\Challenge;
use App\Models\GameLevel;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\ProgressSnapshot;
use App\Models\Setting;
use App\Models\Statistic;
use App\NativeComponents\Screens\ClearThoughtGame;
use Carbon\CarbonImmutable;
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

test('every level bundles enough active original challenges for its round count', function () {
    $levels = GameLevel::query()
        ->whereHas('game', fn ($query) => $query->where('slug', 'clear-thought'))
        ->get();

    expect($levels)->toHaveCount(3);

    foreach ($levels as $level) {
        $challenges = Challenge::query()->active()->where('game_level_id', $level->getKey())->get();

        expect($challenges->count())->toBeGreaterThanOrEqual($level->round_count)
            ->and($challenges->pluck('mode')->unique()->count())->toBe(3);

        foreach ($challenges as $challenge) {
            expect($challenge->prompt)->not->toBe('')
                ->and($challenge->explanation)->not->toBe('')
                ->and((string) data_get($challenge->payload, 'answer_text'))->not->toBe('')
                ->and($challenge->accepted_answers)->not->toBeEmpty();
        }
    }
});

test('the instructions present the premise without creating round evidence', function () {
    $session = preparedClearThoughtSession($this->profile);

    Native::visit('/workout/game/clear-thought/'.$session->getKey())
        ->assertScreen(ClearThoughtGame::class)
        ->assertSet('phase', 'instructions')
        ->assertSee('Say more with less.')
        ->assertSee('SENTENCES')
        ->assertSee('WAYS')
        ->assertSee('Begin Clear Thought')
        ->assertSee('Back to Workout')
        ->assertAccessible();

    expect($session->fresh()->rounds()->count())->toBe(0);
});

test('a perfect session records authoritative evidence progress and statistics', function () {
    $session = preparedClearThoughtSession($this->profile);

    $game = finishClearThoughtPerfectly(
        Native::visit('/workout/game/clear-thought/'.$session->getKey()),
    );

    $game->assertSet('phase', 'game_result')
        ->assertSee('Clarity held.')
        ->assertSee('PERSONAL BEST')
        ->assertSee('ACCURACY')
        ->assertSee('100%')
        ->assertSee('6 of 6 clear')
        ->assertSee('No hints needed')
        ->assertSee('SAVED PRIVATELY ON THIS DEVICE')
        ->assertAccessible();

    $session->refresh();

    expect($session->status)->toBe(SessionStatus::Completed)
        ->and($session->correct_count)->toBe(6)
        ->and($session->incorrect_count)->toBe(0)
        ->and($session->accuracy)->toBe(100.0)
        ->and($session->score)->toBeGreaterThan(0)
        ->and($session->hint_count)->toBe(0)
        ->and($session->rounds()->count())->toBe(6)
        ->and($session->rounds()->whereNull('challenge_id')->count())->toBe(0)
        ->and(ProgressSnapshot::query()->where('game_session_id', $session->getKey())->count())
        ->toBeGreaterThan(0)
        ->and(Statistic::query()->whereBelongsTo($this->profile)->where('scope_key', 'game:clear_thought')->exists())
        ->toBeTrue();
});

test('a wrong choice allows a second attempt before recording an honest incorrect round', function () {
    $session = preparedClearThoughtSession($this->profile);
    $game = Native::visit('/workout/game/clear-thought/'.$session->getKey())
        ->tap('Begin Clear Thought');

    while (currentClearThoughtChallenge($game)->mode !== ClearThoughtMode::ChooseClearestSentence) {
        answerClearThoughtCorrectly($game);
        $game->call('continueAfterReflection');
    }

    answerClearThoughtIncorrectly($game);

    $game->assertSet('phase', 'challenge')
        ->assertSet('attemptsUsed', 1)
        ->assertSee('Not quite. Read it once more and adjust.')
        ->assertAccessible();

    answerClearThoughtIncorrectly($game);

    $game->assertSet('phase', 'reflection')
        ->assertSee('A CLEARER VERSION')
        ->assertSee('Here is the clear form.')
        ->assertAccessible();

    $lastRound = $session->fresh()->rounds()->reorder('round_number', 'desc')->firstOrFail();

    expect($lastRound->outcome)->toBe(RoundOutcome::Incorrect)
        ->and((int) data_get($lastRound->response, 'attempts'))->toBe(2);
});

test('a revealed hint is persisted with the answered round', function () {
    $session = preparedClearThoughtSession($this->profile);
    $game = Native::visit('/workout/game/clear-thought/'.$session->getKey())
        ->tap('Begin Clear Thought')
        ->call('revealHint')
        ->assertSet('hintVisible', true);

    answerClearThoughtCorrectly($game);

    $round = $session->fresh()->rounds()->firstOrFail();

    expect($round->hint_used)->toBeTrue()
        ->and($round->outcome)->toBe(RoundOutcome::Correct);
});

test('a mid-round checkpoint restores the same challenge and selections after re-entry', function () {
    $session = preparedClearThoughtSession($this->profile);
    $game = Native::visit('/workout/game/clear-thought/'.$session->getKey())
        ->tap('Begin Clear Thought');

    answerClearThoughtCorrectly($game);
    $game->call('continueAfterReflection');

    $challenge = currentClearThoughtChallenge($game);
    $firstSegmentId = (string) data_get($challenge->payload, 'segments.0.id', '');

    if ($challenge->mode === ClearThoughtMode::ReorderSentence && $firstSegmentId !== '') {
        $game->call('tapSegment', $firstSegmentId);
    }

    $resumed = Native::visit('/workout/game/clear-thought/'.$session->getKey())
        ->assertSet('phase', 'challenge')
        ->assertSet('roundNumber', 2)
        ->assertSet('totalRounds', 6);

    if ($challenge->mode === ClearThoughtMode::ReorderSentence && $firstSegmentId !== '') {
        expect(collect($resumed->get('arranged'))->pluck('id')->all())->toBe([$firstSegmentId]);
    }

    $resumed->assertAccessible();
});

test('foreign unprepared and completed sessions are guarded honestly', function () {
    $workout = app(WorkoutService::class)->generateToday($this->profile);
    $signalItem = $workout->items->firstOrFail(
        fn ($item): bool => $item->game->type === GameType::SignalShift,
    );
    $signalSession = app(GameSessionService::class)->startForWorkoutItem($this->profile, $signalItem);

    Native::visit('/workout/game/clear-thought/'.$signalSession->getKey())
        ->assertSet('screenState', 'error')
        ->assertSee('Clear Thought unavailable');

    $clearItem = $workout->items->firstOrFail(
        fn ($item): bool => $item->game->type === GameType::ClearThought,
    );
    $unprepared = app(GameSessionService::class)->startForWorkoutItem($this->profile, $clearItem);

    Native::visit('/workout/game/clear-thought/'.$unprepared->getKey())
        ->assertReplacedWith('/workout/preparation/'.$unprepared->getKey());

    app(GameSessionService::class)->checkpoint($unprepared, ['prepared' => true]);
    finishClearThoughtPerfectly(
        Native::visit('/workout/game/clear-thought/'.$unprepared->getKey()),
    );

    Native::visit('/workout/game/clear-thought/'.$unprepared->getKey())
        ->assertReplacedWith('/workout/transition/'.$unprepared->fresh()->daily_workout_item_id);
});

test('reduced motion removes authored clear thought durations', function () {
    Setting::query()->whereBelongsTo($this->profile)->update(['reduced_motion' => true]);

    $session = preparedClearThoughtSession($this->profile);

    Native::visit('/workout/game/clear-thought/'.$session->getKey())
        ->assertSet('reducedMotion', true)
        ->assertSet('motionDuration', 0)
        ->assertSet('feedbackMotionDuration', 0)
        ->assertAccessible();
});

test('later sessions rotate to different bundled challenges deterministically', function () {
    $firstSession = preparedClearThoughtSession($this->profile);
    finishClearThoughtPerfectly(
        Native::visit('/workout/game/clear-thought/'.$firstSession->getKey()),
    );

    CarbonImmutable::setTestNow('2026-07-19 10:30:00');

    $secondSession = preparedClearThoughtSession($this->profile);
    $second = Native::visit('/workout/game/clear-thought/'.$secondSession->getKey());

    expect($second->get('challengeIds'))->not->toBe(
        GameSession::query()->findOrFail($firstSession->getKey())->rounds()->pluck('challenge_id')->all(),
    )->and(count($second->get('challengeIds')))->toBe(6);
});
