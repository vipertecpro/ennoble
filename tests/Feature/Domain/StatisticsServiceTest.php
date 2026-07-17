<?php

use App\Domain\Games\SignalShift\SignalShiftScoringService;
use App\Domain\Statistics\StatisticsService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Models\DailyWorkout;
use App\Models\Game;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Statistic;

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
});
