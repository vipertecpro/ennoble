<?php

use App\Domain\Games\SignalShift\SignalShiftScoringService;
use App\Domain\Statistics\StatisticsService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Models\DailyWorkout;
use App\Models\DailyWorkoutItem;
use App\Models\Game;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Statistic;
use Carbon\CarbonImmutable;

test('statistics calculations preserve unavailable values and validate counts', function () {
    $service = app(StatisticsService::class);

    expect($service->calculateAccuracy(0, 0))->toBeNull()
        ->and($service->calculateAccuracy(3, 4))->toBe(75.0)
        ->and($service->calculateCompletionRate(0, 0))->toBeNull()
        ->and($service->calculateCompletionRate(2, 3))->toBe(67)
        ->and($service->calculateAverageResponseTime(collect([
            new GameRound(['response_ms' => 800]),
            new GameRound(['response_ms' => 1200]),
            new GameRound(['response_ms' => null]),
        ])))->toBe(1000);

    expect(fn () => $service->calculateAccuracy(3, 2))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $service->calculateCompletionRate(3, 2))
        ->toThrow(InvalidArgumentException::class);
});

test('game previews combine aggregate personal bests with session history', function () {
    $profile = Profile::factory()->create();
    $game = Game::query()->where('type', GameType::SignalShift)->firstOrFail();
    $level = $game->levels()->firstOrFail();

    GameSession::factory()->completed()->create([
        'profile_id' => $profile->getKey(),
        'game_id' => $game->getKey(),
        'game_level_id' => $level->getKey(),
        'started_at' => now()->subDay(),
        'completed_at' => now()->subDay()->addMinutes(4),
    ]);
    GameSession::factory()->create([
        'profile_id' => $profile->getKey(),
        'game_id' => $game->getKey(),
        'game_level_id' => $level->getKey(),
        'started_at' => now(),
        'last_interaction_at' => now(),
    ]);
    Statistic::factory()
        ->for($profile)
        ->for($game)
        ->create([
            'scope_key' => 'game:signal_shift',
            'sessions_completed' => 1,
            'best_score' => 975,
        ]);

    $preview = app(StatisticsService::class)->gamePreviews($profile)->get($game->getKey());

    expect($preview)
        ->best_score->toBe(975)
        ->completion_count->toBe(1)
        ->completion_rate->toBe(50)
        ->session_count->toBe(2)
        ->and($preview['last_played_at']->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('completed session statistics are overall per-game and idempotent', function () {
    $profile = Profile::factory()->create();
    $game = Game::query()->where('type', GameType::SignalShift)->firstOrFail();
    $level = $game->levels()->firstOrFail();
    $session = GameSession::factory()->create([
        'profile_id' => $profile->getKey(),
        'game_id' => $game->getKey(),
        'game_level_id' => $level->getKey(),
        'status' => SessionStatus::Completed,
        'completed_at' => now(),
    ]);
    GameRound::factory()->create([
        'game_session_id' => $session->getKey(),
        'round_number' => 1,
        'outcome' => RoundOutcome::Correct,
        'response_ms' => 1000,
        'combo' => 1,
    ]);
    GameRound::factory()->create([
        'game_session_id' => $session->getKey(),
        'round_number' => 2,
        'outcome' => RoundOutcome::Correct,
        'response_ms' => 800,
        'combo' => 2,
    ]);
    GameRound::factory()->create([
        'game_session_id' => $session->getKey(),
        'round_number' => 3,
        'outcome' => RoundOutcome::Incorrect,
        'response_ms' => 500,
        'combo' => 0,
    ]);
    $result = app(SignalShiftScoringService::class)->score($session->rounds()->get());
    $session->update([
        'score' => $result->score,
        'accuracy' => $result->accuracy,
        'average_response_ms' => $result->averageResponseMs,
        'correct_count' => $result->correctCount,
        'incorrect_count' => $result->incorrectCount,
        'best_combo' => $result->bestCombo,
    ]);
    $service = app(StatisticsService::class);

    $service->recordGameSession($session, $result);
    $service->recordGameSession($session, $result);

    $overall = Statistic::query()->whereBelongsTo($profile)->overall()->firstOrFail();
    $perGame = Statistic::query()
        ->whereBelongsTo($profile)
        ->where('scope_key', 'game:signal_shift')
        ->firstOrFail();

    expect($overall->sessions_completed)->toBe(1)
        ->and($overall->accuracy)->toBe(66.67)
        ->and($overall->average_response_ms)->toBe(767)
        ->and($overall->best_score)->toBe($result->score)
        ->and($perGame->sessions_completed)->toBe(1)
        ->and($perGame->longest_combo)->toBe(2)
        ->and($session->refresh()->statistics_recorded_at)->not->toBeNull();
});

test('workout statistics derive current and longest streaks across gaps', function () {
    CarbonImmutable::setTestNow('2026-07-18 10:00:00');
    $profile = Profile::factory()->create();
    $service = app(StatisticsService::class);

    foreach (['2026-07-14', '2026-07-15', '2026-07-17'] as $date) {
        $workout = DailyWorkout::factory()->completed()->for($profile)->create([
            'workout_date' => $date,
            'training_seconds' => 300,
        ]);
        $service->recordWorkoutCompletion($workout);
    }

    $overall = Statistic::query()->whereBelongsTo($profile)->overall()->firstOrFail();

    expect($overall->workouts_completed)->toBe(3)
        ->and($overall->training_seconds)->toBe(900)
        ->and($overall->current_streak)->toBe(1)
        ->and($overall->longest_streak)->toBe(2)
        ->and($overall->last_workout_date->toDateString())->toBe('2026-07-17');

    CarbonImmutable::setTestNow();
});

test('a lapsed streak decays at write time and restarts with new evidence', function () {
    CarbonImmutable::setTestNow('2026-07-18 10:00:00');
    $profile = Profile::factory()->create();
    $service = app(StatisticsService::class);

    foreach (['2026-07-14', '2026-07-15'] as $date) {
        $workout = DailyWorkout::factory()->completed()->for($profile)->create([
            'workout_date' => $date,
            'training_seconds' => 300,
        ]);
        $service->recordWorkoutCompletion($workout);
    }

    $overall = Statistic::query()->whereBelongsTo($profile)->overall()->firstOrFail();

    expect($overall->current_streak)->toBe(0)
        ->and($overall->longest_streak)->toBe(2);

    $todaysWorkout = DailyWorkout::factory()->completed()->for($profile)->create([
        'workout_date' => '2026-07-18',
        'training_seconds' => 300,
    ]);
    $service->recordWorkoutCompletion($todaysWorkout);

    expect($overall->refresh()->current_streak)->toBe(1)
        ->and($overall->longest_streak)->toBe(2);

    CarbonImmutable::setTestNow();
});

test('a stored streak reads truthfully as days pass without new workouts', function () {
    CarbonImmutable::setTestNow('2026-07-18 10:00:00');
    $profile = Profile::factory()->create();
    $statistic = Statistic::factory()->for($profile)->create([
        'scope_key' => 'overall',
        'current_streak' => 4,
        'longest_streak' => 4,
        'last_workout_date' => '2026-07-17',
    ]);

    expect($statistic->current_streak)->toBe(4);

    CarbonImmutable::setTestNow('2026-07-19 10:00:00');

    expect($statistic->fresh()->current_streak)->toBe(0)
        ->and($statistic->fresh()->longest_streak)->toBe(4);

    CarbonImmutable::setTestNow();
});

test('daily summary clamps implausible session durations to the training bound', function () {
    CarbonImmutable::setTestNow('2026-07-18 10:00:00');
    $profile = Profile::factory()->create();
    $workout = DailyWorkout::factory()->completed()->for($profile)->create([
        'workout_date' => '2026-07-18',
    ]);
    $game = Game::query()->where('type', GameType::SignalShift)->firstOrFail();
    $item = DailyWorkoutItem::factory()->create([
        'daily_workout_id' => $workout->getKey(),
        'game_id' => $game->getKey(),
        'game_level_id' => $game->levels()->firstOrFail()->getKey(),
    ]);
    GameSession::factory()->completed()->create([
        'profile_id' => $profile->getKey(),
        'game_id' => $item->game_id,
        'game_level_id' => $item->game_level_id,
        'daily_workout_item_id' => $item->getKey(),
        'started_at' => now()->subHours(30),
        'completed_at' => now(),
    ]);

    $summary = app(StatisticsService::class)->dailySummary($workout);

    expect($summary['training_seconds'])->toBe(21600)
        ->and($summary['has_gameplay_evidence'])->toBeTrue();

    CarbonImmutable::setTestNow();
});
