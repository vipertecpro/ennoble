<?php

namespace App\Domain\Games;

use App\Domain\Achievements\AchievementService;
use App\Domain\Games\ClearThought\ClearThoughtScoringService;
use App\Domain\Games\Contracts\GameScoringService;
use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\SignalShift\SignalShiftScoringService;
use App\Domain\Progress\ProgressService;
use App\Domain\Statistics\StatisticsService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
use App\Enums\WorkoutStatus;
use App\Models\DailyWorkoutItem;
use App\Models\Game;
use App\Models\GameLevel;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;
use LogicException;

final class GameSessionService
{
    /**
     * Create the focused game-session lifecycle service.
     */
    public function __construct(
        private readonly SignalShiftScoringService $signalShiftScoringService,
        private readonly ClearThoughtScoringService $clearThoughtScoringService,
        private readonly ProgressService $progressService,
        private readonly StatisticsService $statisticsService,
        private readonly AchievementService $achievementService,
    ) {}

    /**
     * Start or resume a game session, optionally within a daily workout item.
     */
    public function start(
        Profile $profile,
        Game $game,
        GameLevel $level,
        ?DailyWorkoutItem $workoutItem = null,
        ?string $mode = null,
    ): GameSession {
        if ($level->game_id !== $game->getKey()) {
            throw new LogicException('The selected level does not belong to the game.');
        }

        if ($workoutItem !== null
            && ($workoutItem->game_id !== $game->getKey()
                || $workoutItem->workout->profile_id !== $profile->getKey())) {
            throw new LogicException('The workout item does not match the requested profile and game.');
        }

        return DB::transaction(function () use ($profile, $game, $level, $workoutItem, $mode): GameSession {
            if ($workoutItem !== null) {
                $existingSession = GameSession::query()
                    ->whereBelongsTo($workoutItem, 'workoutItem')
                    ->resumable()
                    ->when(
                        $mode === null,
                        fn ($query) => $query->whereNull('mode'),
                        fn ($query) => $query->where('mode', $mode),
                    )
                    ->with(['game', 'level', 'rounds'])
                    ->latest('started_at')
                    ->first();

                if ($existingSession !== null) {
                    return $existingSession;
                }

                $workoutItem->update([
                    'status' => WorkoutStatus::InProgress,
                    'started_at' => $workoutItem->started_at ?? now(),
                ]);
                $workoutItem->workout->update([
                    'status' => WorkoutStatus::InProgress,
                    'started_at' => $workoutItem->workout->started_at ?? now(),
                ]);
            }

            return GameSession::query()->create([
                'profile_id' => $profile->getKey(),
                'game_id' => $game->getKey(),
                'game_level_id' => $level->getKey(),
                'daily_workout_item_id' => $workoutItem?->getKey(),
                'status' => SessionStatus::InProgress,
                'mode' => $mode,
                'snapshot_version' => 1,
                'current_round' => 0,
                'state_snapshot' => ['version' => 1],
                'started_at' => now(),
                'last_interaction_at' => now(),
            ])->load(['game', 'level', 'rounds']);
        });
    }

    /**
     * Start the implemented native game runner for a workout item.
     */
    public function startForWorkoutItem(
        Profile $profile,
        DailyWorkoutItem $workoutItem,
    ): GameSession {
        return match ($workoutItem->game->type) {
            GameType::ClearThought => $this->startGameplay($profile, $workoutItem),
            GameType::SignalShift => $this->startGameplay($profile, $workoutItem),
        };
    }

    /**
     * Persist non-gameplay session state without inventing round evidence.
     *
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function checkpoint(GameSession $gameSession, array $stateSnapshot): GameSession
    {
        return DB::transaction(function () use ($gameSession, $stateSnapshot): GameSession {
            $session = GameSession::query()
                ->lockForUpdate()
                ->findOrFail($gameSession->getKey());

            if ($session->status !== SessionStatus::InProgress) {
                throw new LogicException('Only an in-progress session can store a checkpoint.');
            }

            $session->update([
                'state_snapshot' => [
                    'version' => $session->snapshot_version,
                    ...$stateSnapshot,
                ],
                'last_interaction_at' => now(),
            ]);

            return $session->refresh();
        });
    }

    /**
     * Restart an unfinished evidence-backed attempt while preserving the same session identity.
     *
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function restart(GameSession $gameSession, array $stateSnapshot): GameSession
    {
        return DB::transaction(function () use ($gameSession, $stateSnapshot): GameSession {
            $session = GameSession::query()
                ->with('workoutItem')
                ->lockForUpdate()
                ->findOrFail($gameSession->getKey());

            if ($session->isFrameworkPlaceholder()) {
                throw new LogicException('Framework placeholders must use placeholder restart.');
            }

            if ($session->status !== SessionStatus::InProgress) {
                throw new LogicException('Only an in-progress session can be restarted.');
            }

            $session->rounds()->delete();
            $session->update([
                'current_round' => 0,
                'state_snapshot' => [
                    'version' => $session->snapshot_version,
                    ...$stateSnapshot,
                ],
                'score' => null,
                'accuracy' => null,
                'average_response_ms' => null,
                'correct_count' => 0,
                'incorrect_count' => 0,
                'missed_count' => 0,
                'hint_count' => 0,
                'best_combo' => 0,
                'last_interaction_at' => now(),
                'completed_at' => null,
                'statistics_recorded_at' => null,
            ]);

            $session->workoutItem?->update([
                'status' => WorkoutStatus::InProgress,
                'completed_at' => null,
            ]);

            return $session->refresh()->load(['game', 'level', 'rounds', 'workoutItem.workout']);
        });
    }

    /**
     * Append one immutable round and advance its resumable checkpoint atomically.
     *
     * @param  array{
     *     challenge_id?: int|null,
     *     outcome: RoundOutcome|string,
     *     response_ms?: int|null,
     *     score_delta?: int,
     *     combo?: int|null,
     *     hint_used?: bool,
     *     response?: array<string, mixed>|null
     * }  $roundData
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordRound(
        GameSession $gameSession,
        array $roundData,
        array $stateSnapshot,
    ): GameRound {
        return DB::transaction(function () use ($gameSession, $roundData, $stateSnapshot): GameRound {
            $session = GameSession::query()
                ->lockForUpdate()
                ->findOrFail($gameSession->getKey());

            if ($session->status !== SessionStatus::InProgress) {
                throw new LogicException('Only an in-progress session can accept round evidence.');
            }

            $outcome = $roundData['outcome'] instanceof RoundOutcome
                ? $roundData['outcome']
                : RoundOutcome::from($roundData['outcome']);
            $roundNumber = $session->current_round + 1;
            $round = $session->rounds()->create([
                'challenge_id' => $roundData['challenge_id'] ?? null,
                'round_number' => $roundNumber,
                'outcome' => $outcome,
                'response_ms' => $roundData['response_ms'] ?? null,
                'score_delta' => $roundData['score_delta'] ?? 0,
                'combo' => $roundData['combo'] ?? null,
                'hint_used' => $roundData['hint_used'] ?? false,
                'response' => $roundData['response'] ?? null,
            ]);

            $session->update([
                'current_round' => $roundNumber,
                'state_snapshot' => ['version' => $session->snapshot_version, ...$stateSnapshot],
                'correct_count' => $session->correct_count + (int) ($outcome === RoundOutcome::Correct),
                'incorrect_count' => $session->incorrect_count + (int) ($outcome === RoundOutcome::Incorrect),
                'missed_count' => $session->missed_count + (int) ($outcome === RoundOutcome::Missed),
                'hint_count' => $session->hint_count + (int) ($round->hint_used),
                'best_combo' => max($session->best_combo, $round->combo ?? 0),
                'last_interaction_at' => now(),
            ]);

            return $round;
        });
    }

    /**
     * Complete a session and update its evidence-backed aggregates exactly once.
     */
    public function complete(GameSession $gameSession): ScoringResult
    {
        return DB::transaction(function () use ($gameSession): ScoringResult {
            $session = GameSession::query()
                ->with(['game', 'rounds', 'profile', 'workoutItem.workout'])
                ->lockForUpdate()
                ->findOrFail($gameSession->getKey());

            if (! in_array($session->status, [SessionStatus::InProgress, SessionStatus::Completed], true)) {
                throw new LogicException('This session cannot be completed from its current state.');
            }

            if ($session->isFrameworkPlaceholder()) {
                throw new LogicException('Framework placeholder sessions cannot create gameplay evidence.');
            }

            $result = $this->scoringService($session->game->type)->score($session->rounds);

            if ($session->status !== SessionStatus::Completed) {
                $session->update([
                    'status' => SessionStatus::Completed,
                    'score' => $result->score,
                    'accuracy' => $result->accuracy,
                    'average_response_ms' => $result->averageResponseMs,
                    'correct_count' => $result->correctCount,
                    'incorrect_count' => $result->incorrectCount,
                    'missed_count' => $result->missedCount,
                    'hint_count' => $result->hintCount,
                    'best_combo' => $result->bestCombo,
                    'state_snapshot' => null,
                    'last_interaction_at' => now(),
                    'completed_at' => now(),
                ]);

                $session->workoutItem?->update([
                    'status' => WorkoutStatus::Completed,
                    'completed_at' => now(),
                ]);
            }

            $skillDelta = $this->skillDelta($result);
            $this->progressService->updateSkillValues(
                profile: $session->profile,
                skillDeltas: collect($session->game->skill_keys)
                    ->mapWithKeys(fn (string $skillKey): array => [$skillKey => $skillDelta])
                    ->all(),
                gameSession: $session,
            );
            $this->statisticsService->recordGameSession($session, $result);
            $this->achievementService->evaluate(
                profile: $session->profile,
                gameSession: $session,
            );

            return $result;
        });
    }

    /**
     * Retrieve the newest resumable attempt for a workout item.
     */
    public function resume(DailyWorkoutItem $workoutItem): ?GameSession
    {
        return GameSession::query()
            ->whereBelongsTo($workoutItem, 'workoutItem')
            ->resumable()
            ->with(['game', 'level', 'rounds.challenge'])
            ->latest('last_interaction_at')
            ->first();
    }

    private function scoringService(GameType $gameType): GameScoringService
    {
        return match ($gameType) {
            GameType::SignalShift => $this->signalShiftScoringService,
            GameType::ClearThought => $this->clearThoughtScoringService,
        };
    }

    private function startGameplay(
        Profile $profile,
        DailyWorkoutItem $workoutItem,
    ): GameSession {
        GameSession::query()
            ->whereBelongsTo($workoutItem, 'workoutItem')
            ->where('mode', GameSession::FRAMEWORK_PLACEHOLDER_MODE)
            ->resumable()
            ->delete();

        return $this->start(
            profile: $profile,
            game: $workoutItem->game,
            level: $workoutItem->level,
            workoutItem: $workoutItem,
        );
    }

    private function skillDelta(ScoringResult $result): int
    {
        if ($result->accuracy === null) {
            return 0;
        }

        return max(-5, min(10, (int) round(($result->accuracy - 50) / 10)));
    }
}
