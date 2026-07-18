<?php

namespace App\Domain\Games\SignalShift;

use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\GameSessionService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Statistic;
use Illuminate\Support\Collection;
use LogicException;

final class SignalShiftGameService
{
    /**
     * Create the focused Signal Shift runtime service.
     */
    public function __construct(
        private readonly GameSessionService $sessions,
        private readonly SignalShiftScoringService $scoring,
    ) {}

    public function hasCompletedTutorial(Profile $profile): bool
    {
        return GameSession::query()
            ->whereBelongsTo($profile)
            ->completed()
            ->withGameplayEvidence()
            ->whereHas('game', fn ($query) => $query->where('type', GameType::SignalShift))
            ->exists();
    }

    /**
     * Persist one correct or incorrect tap from an authoritative generated stimulus.
     *
     * @param  array<string, mixed>  $stimulus
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordTap(
        GameSession $session,
        array $stimulus,
        int $responseMs,
        int $combo,
        int $gameRound,
        int $wave,
        array $stateSnapshot,
    ): GameRound {
        $this->guardSession($session);
        $outcome = (bool) ($stimulus['is_target'] ?? false)
            ? RoundOutcome::Correct
            : RoundOutcome::Incorrect;

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => $outcome,
                'response_ms' => max(1, min($responseMs, 60000)),
                'score_delta' => $this->scoreDelta($outcome, $responseMs, $combo),
                'combo' => $combo,
                'response' => [
                    'game_round' => $gameRound,
                    'wave' => $wave,
                    'stimulus_id' => (string) ($stimulus['id'] ?? ''),
                    'color' => (string) ($stimulus['color'] ?? ''),
                    'shape' => (string) ($stimulus['shape'] ?? ''),
                    'size' => (string) ($stimulus['size'] ?? ''),
                    'moving' => (bool) ($stimulus['moving'] ?? false),
                    'rotated' => (bool) ($stimulus['rotated'] ?? false),
                    'is_target' => (bool) ($stimulus['is_target'] ?? false),
                ],
            ],
            stateSnapshot: $stateSnapshot,
        );
    }

    /**
     * Persist one missed target when its wave expires.
     *
     * @param  array<string, mixed>  $stimulus
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordMiss(
        GameSession $session,
        array $stimulus,
        int $gameRound,
        int $wave,
        array $stateSnapshot,
    ): GameRound {
        $this->guardSession($session);

        if (! (bool) ($stimulus['is_target'] ?? false)) {
            throw new LogicException('Only an eligible Signal Shift target may be recorded as missed.');
        }

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => RoundOutcome::Missed,
                'score_delta' => -50,
                'combo' => 0,
                'response' => [
                    'game_round' => $gameRound,
                    'wave' => $wave,
                    'stimulus_id' => (string) ($stimulus['id'] ?? ''),
                    'color' => (string) ($stimulus['color'] ?? ''),
                    'shape' => (string) ($stimulus['shape'] ?? ''),
                    'is_target' => true,
                ],
            ],
            stateSnapshot: $stateSnapshot,
        );
    }

    /**
     * Return live score metrics from authoritative persisted evidence.
     */
    public function score(GameSession $session): ScoringResult
    {
        $this->guardSession($session);

        return $this->scoring->score($session->rounds()->get());
    }

    /**
     * Return metrics for one of the three player-facing gameplay rounds.
     *
     * @return array{
     *     accuracy: float|null,
     *     average_response_ms: int|null,
     *     score: int,
     *     best_combo: int,
     *     correct_count: int,
     *     incorrect_count: int,
     *     missed_count: int
     * }
     */
    public function roundMetrics(GameSession $session, int $gameRound): array
    {
        $this->guardSession($session);
        $rounds = $session->rounds()
            ->get()
            ->filter(
                fn (GameRound $round): bool => (int) data_get($round->response, 'game_round') === $gameRound,
            )
            ->values();
        $result = $this->scoring->score($rounds);

        return [
            'accuracy' => $result->accuracy,
            'average_response_ms' => $result->averageResponseMs,
            'score' => $result->score,
            'best_combo' => $result->bestCombo,
            'correct_count' => $result->correctCount,
            'incorrect_count' => $result->incorrectCount,
            'missed_count' => $result->missedCount,
        ];
    }

    public function previousBestScore(GameSession $session): ?int
    {
        $this->guardSession($session);

        return Statistic::query()
            ->whereBelongsTo($session->profile)
            ->where('game_id', $session->game_id)
            ->value('best_score');
    }

    public function complete(GameSession $session): ScoringResult
    {
        $this->guardSession($session);

        return $this->sessions->complete($session);
    }

    /**
     * Reset an unfinished gameplay session without creating a second attempt.
     *
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function restart(GameSession $session, array $stateSnapshot): GameSession
    {
        $this->guardSession($session);

        return $this->sessions->restart($session, $stateSnapshot);
    }

    /**
     * @param  Collection<int, GameRound>  $rounds
     */
    public function scoreRounds(Collection $rounds): ScoringResult
    {
        return $this->scoring->score($rounds);
    }

    private function guardSession(GameSession $session): void
    {
        $session->loadMissing(['game', 'profile']);

        if ($session->game->type !== GameType::SignalShift || $session->isFrameworkPlaceholder()) {
            throw new LogicException('Signal Shift gameplay requires a real Signal Shift session.');
        }
    }

    private function scoreDelta(RoundOutcome $outcome, int $responseMs, int $combo): int
    {
        return match ($outcome) {
            RoundOutcome::Correct => 100
                + max(0, 100 - intdiv(max(1, $responseMs), 20))
                + min($combo * 10, 100),
            RoundOutcome::Incorrect => -75,
            RoundOutcome::Missed => -50,
        };
    }
}
