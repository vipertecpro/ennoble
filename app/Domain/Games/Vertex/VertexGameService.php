<?php

namespace App\Domain\Games\Vertex;

use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\GameSessionService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Statistic;
use LogicException;

/**
 * Barrage runtime service. Generates deterministic waves with
 * {@see VertexGenerator} and records one authoritative round per WAVE.
 *
 * A wave can end two ways and the distinction matters: the player can clear
 * every target before the formation lands (resolved early, which is the
 * skilled outcome and the only one that earns the clean-sweep bonus), or the
 * descent clock can run out with targets still standing.
 *
 * Outcome is decided in strict priority: any false alarm makes the wave
 * Incorrect, because firing on a decoy is a failure of inhibition and should
 * not be redeemable by clearing the rest; otherwise any survivor makes it
 * Missed; otherwise it is Correct. Response time is only recorded when the
 * wave was actually resolved — a wave that timed out has no reaction to time,
 * and averaging the full descent in would make patience look like slowness.
 */
final class VertexGameService
{
    public function __construct(
        private readonly GameSessionService $sessions,
        private readonly VertexGenerator $generator,
        private readonly VertexScoringService $scoring,
    ) {}

    /**
     * Build this session's deterministic waves.
     *
     * @return list<array{criterion: array<string, mixed>, order: string, invaders: list<array{id: int, shape: string, colour: string, is_target: bool}>}>
     */
    public function wavesFor(GameSession $session): array
    {
        $this->guardSession($session);

        $rotation = GameSession::query()
            ->whereBelongsTo($session->profile)
            ->where('game_id', $session->game_id)
            ->completed()
            ->count();

        return $this->generator->generate(
            level: $session->level,
            seed: 'barrage:'.$session->getKey().':rotation:'.$rotation,
            count: max(1, (int) $session->level->round_count),
        );
    }

    /**
     * Persist a resolved wave.
     *
     * @param  array{criterion: array<string, mixed>, order: string, invaders: list<array<string, mixed>>}  $wave
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordWave(
        GameSession $session,
        array $wave,
        int $hits,
        int $falseAlarms,
        int $survivors,
        int $responseMs,
        int $descentMs,
        float $remainingFraction,
        int $combo,
        array $stateSnapshot,
    ): GameRound {
        $this->guardSession($session);

        $outcome = match (true) {
            $falseAlarms > 0 => RoundOutcome::Incorrect,
            $survivors > 0 => RoundOutcome::Missed,
            default => RoundOutcome::Correct,
        };

        $clean = $outcome === RoundOutcome::Correct;
        $storedCombo = $clean ? max(0, $combo) : 0;
        $boundedRemaining = max(0.0, min(1.0, $remainingFraction));

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => $outcome,
                // Only a wave the player actually finished has a reaction time.
                'response_ms' => $survivors > 0 ? null : max(1, min($responseMs, 300000)),
                'score_delta' => $this->scoreDelta($hits, $clean, $boundedRemaining, $storedCombo),
                'combo' => $storedCombo,
                'response' => [
                    'order' => (string) $wave['order'],
                    'criterion' => $wave['criterion'],
                    'formation' => count($wave['invaders']),
                    'targets' => count(array_filter(
                        $wave['invaders'],
                        static fn (array $invader): bool => (bool) $invader['is_target'],
                    )),
                    'hits' => max(0, $hits),
                    'false_alarms' => max(0, $falseAlarms),
                    'survivors' => max(0, $survivors),
                    'descent_ms' => max(1, min($descentMs, 300000)),
                    'remaining_fraction' => round($boundedRemaining, 4),
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

        if ($session->game->type !== GameType::Vertex) {
            throw new LogicException('Barrage gameplay requires a real Barrage session.');
        }
    }

    private function scoreDelta(int $hits, bool $clean, float $remainingFraction, int $combo): int
    {
        $score = max(0, $hits) * 60;

        if (! $clean) {
            return $score;
        }

        return $score
            + 120
            + VertexScoringService::speedBonus($remainingFraction)
            + min($combo * 15, 150);
    }
}
