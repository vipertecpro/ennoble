<?php

use App\Domain\Games\GameSessionService;
use App\Domain\Workout\WorkoutService;
use App\Enums\Difficulty;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Enums\WorkoutStatus;
use App\Models\AchievementUnlock;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\ProgressSnapshot;
use App\Models\Statistic;
use Carbon\CarbonImmutable;

test('workout generation is deterministic unique and ordered for the local date', function () {
    $profile = Profile::factory()->create([
        'difficulty_preference' => Difficulty::Advanced,
    ]);
    $date = CarbonImmutable::parse('2026-07-18');
    $service = app(WorkoutService::class);

    $workout = $service->generateToday($profile, $date);
    $sameWorkout = $service->generateToday($profile, $date);
    $resumed = $service->resume($profile, $date);

    expect($sameWorkout->is($workout))->toBeTrue()
        ->and($resumed?->is($workout))->toBeTrue()
        ->and($workout->items)->toHaveCount(2)
        ->and($workout->items->pluck('position')->all())->toBe([1, 2])
        ->and($workout->items->pluck('game.type')->all())->toBe([
            GameType::SignalShift,
            GameType::ClearThought,
        ])
        ->and($workout->items->every(
            fn ($item): bool => $item->level->difficulty === Difficulty::Advanced,
        ))->toBeTrue()
        ->and($service->estimatedDurationMinutes($workout))->toBe(9)
        ->and($workout->status)->toBe(WorkoutStatus::Pending);
});

test('workout generation fails honestly when bundled content is unavailable', function () {
    $profile = Profile::factory()->create();
    Game::query()->where('type', GameType::ClearThought)->delete();

    expect(fn () => app(WorkoutService::class)->generateToday(
        $profile,
        CarbonImmutable::parse('2026-07-18'),
    ))->toThrow(DomainException::class);
});

test('game checkpoints and completion feed workout progress statistics and achievements', function () {
    $profile = Profile::factory()->create();
    $workoutService = app(WorkoutService::class);
    $sessionService = app(GameSessionService::class);
    $workout = $workoutService->generateToday(
        $profile,
        CarbonImmutable::parse('2026-07-18'),
    );

    foreach ($workout->items as $item) {
        $session = $sessionService->start(
            profile: $profile,
            game: $item->game,
            level: $item->level,
            workoutItem: $item,
        );
        $round = $sessionService->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => RoundOutcome::Correct,
                'response_ms' => 900,
                'combo' => $item->game->type === GameType::SignalShift ? 1 : null,
                'hint_used' => false,
                'response' => ['attempts' => 1],
            ],
            stateSnapshot: ['next_round' => 2],
        );
        $resumed = $sessionService->resume($item);

        expect($round->round_number)->toBe(1)
            ->and($resumed?->is($session))->toBeTrue()
            ->and($resumed?->state_snapshot)->toBe([
                'version' => 1,
                'next_round' => 2,
            ]);

        $firstResult = $sessionService->complete($session);
        $secondResult = $sessionService->complete($session);

        expect($firstResult)->toEqual($secondResult)
            ->and($session->refresh()->status)->toBe(SessionStatus::Completed)
            ->and($session->state_snapshot)->toBeNull();
    }

    $completedWorkout = $workoutService->complete($workout);
    $completedAgain = $workoutService->complete($completedWorkout);
    $overall = Statistic::query()->whereBelongsTo($profile)->overall()->firstOrFail();

    expect($completedAgain->status)->toBe(WorkoutStatus::Completed)
        ->and($completedAgain->summary['sessions_completed'])->toBe(2)
        ->and($completedAgain->summary['accuracy'])->toBe(100)
        ->and($overall->sessions_completed)->toBe(2)
        ->and($overall->workouts_completed)->toBe(1)
        ->and(GameSession::query()->whereBelongsTo($profile)->count())->toBe(2)
        ->and(ProgressSnapshot::query()->whereBelongsTo($profile)->count())->toBe(7)
        ->and(AchievementUnlock::query()->whereBelongsTo($profile)->whereHas(
            'achievement',
            fn ($query) => $query->where('slug', 'first-step'),
        )->count())->toBe(1);
});

test('an incomplete workout cannot be marked complete', function () {
    $profile = Profile::factory()->create();
    $workout = app(WorkoutService::class)->generateToday(
        $profile,
        CarbonImmutable::parse('2026-07-18'),
    );

    expect(fn () => app(WorkoutService::class)->complete($workout))
        ->toThrow(LogicException::class);
});
