<?php

namespace App\Domain\Games\Stack;

use App\Domain\Games\Contracts\ScoringResult;
use App\Domain\Games\GameSessionService;
use App\Enums\GameType;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\Statistic;
use LogicException;

/**
 * Stack runtime service. Deals a deterministic piece sequence with
 * {@see StackGenerator} and records one authoritative round per piece placed.
 *
 * A "round" is a placement. That is the unit the player is judged on and the
 * unit the result screen counts, even though nothing about the game is
 * turn-based in the way the other games are.
 */
final class StackGameService
{
    public function __construct(
        private readonly GameSessionService $sessions,
        private readonly StackGenerator $generator,
        private readonly StackScoringService $scoring,
    ) {}

    /**
     * @return list<string>
     */
    public function sequenceFor(GameSession $session): array
    {
        $this->guardSession($session);

        $pieceCount = max(1, (int) $session->level->round_count);
        $rotation = GameSession::query()
            ->whereBelongsTo($session->profile)
            ->where('game_id', $session->game_id)
            ->completed()
            ->count();

        return $this->generator->generate(
            level: $session->level,
            seed: 'stack:'.$session->getKey().':rotation:'.$rotation,
            count: $pieceCount,
        );
    }

    /**
     * Record one placement.
     *
     * @param  array<string, mixed>  $stateSnapshot
     */
    public function recordPlacement(
        GameSession $session,
        string $piece,
        int $linesCleared,
        int $holesAdded,
        int $thinkMs,
        int $combo,
        array $stateSnapshot,
    ): GameRound {
        $this->guardSession($session);

        $clean = $holesAdded <= 0;
        $outcome = $clean ? RoundOutcome::Correct : RoundOutcome::Incorrect;
        $storedCombo = $clean ? max(0, $combo) : 0;
        $boundedLines = max(0, min($linesCleared, 4));

        return $this->sessions->recordRound(
            gameSession: $session,
            roundData: [
                'outcome' => $outcome,
                // Time spent placing the piece. Meaningful on its own here:
                // a run of fast clean placements is a different skill from a
                // run of slow ones.
                'response_ms' => max(1, min($thinkMs, 300000)),
                'score_delta' => $this->scoreDelta($outcome, $boundedLines, $storedCombo),
                'combo' => $storedCombo,
                'response' => [
                    'piece' => $piece,
                    'lines' => $boundedLines,
                    'holes_added' => max(0, $holesAdded),
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

        if ($session->game->type !== GameType::Stack) {
            throw new LogicException('Stack gameplay requires a real Stack session.');
        }
    }

    private function scoreDelta(RoundOutcome $outcome, int $lines, int $combo): int
    {
        $linePoints = [1 => 100, 2 => 300, 3 => 500, 4 => 800][$lines] ?? 0;

        if ($outcome !== RoundOutcome::Correct) {
            return $linePoints;
        }

        return $linePoints + 25 + min($combo * 5, 100);
    }
}
