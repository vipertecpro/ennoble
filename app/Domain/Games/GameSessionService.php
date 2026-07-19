<?php

namespace App\Domain\Games;

use App\Domain\Achievements\AchievementService;
use App\Domain\Games\Contracts\GameScoringService;
use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\QuickMath\QuickMathScoringService;
use App\Domain\Games\WordMatch\WordMatchScoringService;
use App\Domain\Progress\ProgressService;
use App\Domain\Statistics\StatisticsService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Enums\SessionStatus;
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
        private readonly WordMatchScoringService $wordMatchScoringService,
        private readonly QuickMathScoringService $quickMathScoringService,
        private readonly ProgressService $progressService,
        private readonly StatisticsService $statisticsService,
        private readonly AchievementService $achievementService,
    ) {}

    /**
     * Start a fresh, standalone free-play session for a game tile launch.
     */
    public function start(Profile $profile, Game $game, GameLevel $level, ?string $mode = null): GameSession
    {
        if ($level->game_id !== $game->getKey()) {
            throw new LogicException('The selected level does not belong to the game.');
        }

        return GameSession::query()->create([
            'profile_id' => $profile->getKey(),
            'game_id' => $game->getKey(),
            'game_level_id' => $level->getKey(),
            'status' => SessionStatus::InProgress,
            'mode' => $mode,
            'snapshot_version' => 1,
            'current_round' => 0,
            'state_snapshot' => ['version' => 1],
            'started_at' => now(),
            'last_interaction_at' => now(),
        ])->load(['game', 'level', 'rounds']);
    }

    /**
     * Start a fresh, standalone free-play session. Each launch creates a new
     * session, matching the "play a game tile" flow.
     */
    public function startFreePlay(Profile $profile, Game $game, GameLevel $level): GameSession
    {
        return $this->start(profile: $profile, game: $game, level: $level);
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
                ->lockForUpdate()
                ->findOrFail($gameSession->getKey());

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

            return $session->refresh()->load(['game', 'level', 'rounds']);
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
                ->with(['game', 'rounds', 'profile'])
                ->lockForUpdate()
                ->findOrFail($gameSession->getKey());

            if (! in_array($session->status, [SessionStatus::InProgress, SessionStatus::Completed], true)) {
                throw new LogicException('This session cannot be completed from its current state.');
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

    private function scoringService(GameType $gameType): GameScoringService
    {
        return match ($gameType) {
            GameType::WordMatch => $this->wordMatchScoringService,
            GameType::QuickMath => $this->quickMathScoringService,
        };
    }

    private function skillDelta(ScoringResult $result): int
    {
        if ($result->accuracy === null) {
            return 0;
        }

        return max(-5, min(10, (int) round(($result->accuracy - 50) / 10)));
    }
}
