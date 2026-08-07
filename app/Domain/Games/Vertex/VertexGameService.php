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
 * Vertex runtime service. Generates a deterministic object stream with
 * {@see VertexGenerator} and records authoritative round evidence through the
 * shared {@see GameSessionService}.
 *
 * The one thing worth knowing: a round can resolve by ACTION (the player
 * struck) or by INACTION (the object flew past). Both can be right and both can
 * be wrong, so there are two record methods and neither is a "timeout" in the
 * usual sense — letting a decoy pass is a correct answer that happens to be
 * made of silence.
 *
 * Depth is stored on every struck round as a 0..1 fraction of the flight, which
 * is what {@see VertexScoringService} pays the precision bonus from. It is
 * derived from elapsed time rather than anything the view reports, because
 * finger position and render state never reach PHP.
 */
final class VertexGameService
{
    public function __construct(
        private readonly GameSessionService $sessions,
        private readonly VertexGenerator $generator,
        private readonly VertexScoringService $scoring,
    ) {}

    /**
     * Build this session's deterministic object stream.
     *
     * @return list<array{key: string, shape: string, is_go: bool}>
     */
    public function roundsFor(GameSession $session): array
    {
        $this->guardSession($session);

        $roundCount = max(1, (int) $session->level->round_count);
        $rotation = GameSession::query()
            ->whereBelongsTo($session->profile)
            ->where('game_id', $session->game_id)
            ->completed()
            ->count();

        return $this->generator->generate(
            level: $session->level,
            seed: 'vertex:'.$session->getKey().':rotation:'.$rotation,
            count: $roundCount,
        );
    }

    /**
     * Persist a struck object. Striking a target is a hit; striking a decoy is
     * a false alarm — the error the whole game is built to provoke.
     *
     * @param  array{key: string, shape: string, is_go: bool}  $round
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordStrike(
        GameSession $session,
        array $round,
        int $responseMs,
        int $flightMs,
        int $combo,
        array $stateSnapshot,
    ): GameRound {
        $this->guardSession($session);

        $isGo = (bool) $round['is_go'];
        $outcome = $isGo ? RoundOutcome::Correct : RoundOutcome::Incorrect;
        $boundedResponseMs = max(1, min($responseMs, 300000));
        $boundedFlightMs = max(1, min($flightMs, 300000));
        $depth = min(1.0, $boundedResponseMs / $boundedFlightMs);
        $storedCombo = $isGo ? max(0, $combo) : 0;

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => $outcome,
                'response_ms' => $boundedResponseMs,
                'score_delta' => $isGo
                    ? 100 + VertexScoringService::depthBonus($depth) + min($storedCombo * 10, 120)
                    : 0,
                'combo' => $storedCombo,
                'response' => [
                    'key' => (string) $round['key'],
                    'shape' => (string) $round['shape'],
                    'is_go' => $isGo,
                    'struck' => true,
                    'depth' => round($depth, 4),
                    'flight_ms' => $boundedFlightMs,
                ],
            ],
            stateSnapshot: $stateSnapshot,
        );
    }

    /**
     * Persist an object that flew past untouched. For a decoy that is the
     * correct, disciplined answer; for a target it is a genuine miss.
     *
     * @param  array{key: string, shape: string, is_go: bool}  $round
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordPass(
        GameSession $session,
        array $round,
        int $flightMs,
        int $combo,
        array $stateSnapshot,
    ): GameRound {
        $this->guardSession($session);

        $isGo = (bool) $round['is_go'];
        $outcome = $isGo ? RoundOutcome::Missed : RoundOutcome::Correct;
        $storedCombo = $isGo ? 0 : max(0, $combo);

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => $outcome,
                // No strike means no reaction to time — see the class docblock.
                'response_ms' => null,
                'score_delta' => $isGo ? 0 : 30 + min($storedCombo * 10, 60),
                'combo' => $storedCombo,
                'response' => [
                    'key' => (string) $round['key'],
                    'shape' => (string) $round['shape'],
                    'is_go' => $isGo,
                    'struck' => false,
                    'depth' => 1.0,
                    'flight_ms' => max(1, min($flightMs, 300000)),
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
            throw new LogicException('Vertex gameplay requires a real Vertex session.');
        }
    }
}
