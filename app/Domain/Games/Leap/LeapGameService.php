<?php

namespace App\Domain\Games\Leap;

use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\GameSessionService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Statistic;
use LogicException;

/**
 * Leap runtime service. Generates a deterministic course with
 * {@see LeapGenerator} and records one authoritative round per obstacle
 * through the shared {@see GameSessionService}.
 *
 * An obstacle is a "round" even though nothing about the run is turn-based:
 * it is the unit the player is judged on, which is what the evidence model and
 * the result screen are both built around. There is no timeout outcome —
 * failing to jump is a collision, not a missed prompt.
 */
final class LeapGameService
{
    public function __construct(
        private readonly GameSessionService $sessions,
        private readonly LeapGenerator $generator,
        private readonly LeapScoringService $scoring,
    ) {}

    /**
     * @return list<array{gap_ms: int, travel_ms: int, height: int}>
     */
    public function courseFor(GameSession $session): array
    {
        $this->guardSession($session);

        $obstacleCount = max(1, (int) $session->level->round_count);
        $rotation = GameSession::query()
            ->whereBelongsTo($session->profile)
            ->where('game_id', $session->game_id)
            ->completed()
            ->count();

        return $this->generator->generate(
            level: $session->level,
            seed: 'leap:'.$session->getKey().':rotation:'.$rotation,
            count: $obstacleCount,
        );
    }

    /**
     * Record one obstacle's outcome.
     *
     * @param  array{gap_ms: int, travel_ms: int, height: int}  $obstacle
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordObstacle(
        GameSession $session,
        array $obstacle,
        bool $cleared,
        ?int $leadMs,
        int $combo,
        array $stateSnapshot,
    ): GameRound {
        $this->guardSession($session);

        $outcome = $cleared ? RoundOutcome::Correct : RoundOutcome::Incorrect;
        $storedCombo = $cleared ? max(0, $combo) : 0;
        $travelMs = max(1, (int) $obstacle['travel_ms']);

        // How far ahead of the obstacle the jump started. Only meaningful when
        // it was cleared — a collision has no jump to measure.
        $boundedLeadMs = $cleared && $leadMs !== null ? max(1, min($leadMs, 300000)) : null;

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => $outcome,
                'response_ms' => $boundedLeadMs,
                'score_delta' => $this->scoreDelta($outcome, $travelMs, $storedCombo),
                'combo' => $storedCombo,
                'response' => [
                    'travel_ms' => $travelMs,
                    'height' => (int) $obstacle['height'],
                    'cleared' => $cleared,
                    'lead_ms' => $boundedLeadMs,
                ],
            ],
            stateSnapshot: $stateSnapshot,
        );
    }

    public function score(GameSession $session): ScoringResult
    {
        $this->guardSession($session);

        return $this->scoring->score($session->rounds()->get());
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

    private function guardSession(GameSession $session): void
    {
        $session->loadMissing(['game', 'level', 'profile']);

        if ($session->game->type !== GameType::Leap) {
            throw new LogicException('Leap gameplay requires a real Leap session.');
        }
    }

    private function scoreDelta(RoundOutcome $outcome, int $travelMs, int $combo): int
    {
        if ($outcome !== RoundOutcome::Correct) {
            return 0;
        }

        $fraction = (2600 - $travelMs) / (2600 - 1200);
        $paceBonus = max(0, min(60, (int) round($fraction * 60)));

        return 100 + $paceBonus + min($combo * 10, 150);
    }
}
