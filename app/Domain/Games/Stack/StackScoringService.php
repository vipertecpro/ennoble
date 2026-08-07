<?php

namespace App\Domain\Games\Stack;

use App\Domain\Games\Contracts\GameScoringService;
use App\Domain\Games\Contracts\ScoringResult;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use Illuminate\Support\Collection;

final class StackScoringService implements GameScoringService
{
    /**
     * Clearing rows together is worth far more than clearing them one at a
     * time — that gap is what makes building up deliberately, rather than
     * flattening greedily, the better strategy. It is the whole risk/reward
     * shape of the game and it lives in this table.
     *
     * @var array<int, int>
     */
    private const LINE_POINTS = [1 => 100, 2 => 300, 3 => 500, 4 => 800];

    /** Paid for a placement that covers nothing it cannot reach again. */
    private const CLEAN_PLACEMENT = 25;

    /**
     * Score a Stack run from authoritative round evidence — one round per
     * piece placed.
     *
     * A round is CORRECT when the placement created no new holes, not when it
     * cleared a line. Holes are what a player actually controls and what
     * ruins a board; a line clear is the reward, and it is paid separately.
     * That also makes "accuracy" mean something here: the share of pieces
     * placed without burying a cell.
     *
     * @param  Collection<int, GameRound>  $rounds
     */
    public function score(Collection $rounds): ScoringResult
    {
        $correctCount = $rounds->where('outcome', RoundOutcome::Correct)->count();
        $incorrectCount = $rounds->where('outcome', RoundOutcome::Incorrect)->count();
        $missedCount = $rounds->where('outcome', RoundOutcome::Missed)->count();
        $attemptedCount = $correctCount + $incorrectCount + $missedCount;
        $accuracy = $attemptedCount === 0
            ? null
            : round(($correctCount / $attemptedCount) * 100, 2);

        $timedResponses = $rounds->whereNotNull('response_ms');
        $averageResponseMs = $timedResponses->isEmpty()
            ? null
            : (int) round($timedResponses->avg('response_ms'));

        $score = 0;

        foreach ($rounds->sortBy('round_number') as $round) {
            $score += self::LINE_POINTS[$this->linesCleared($round)] ?? 0;

            if ($round->outcome === RoundOutcome::Correct) {
                $score += self::CLEAN_PLACEMENT + min(((int) ($round->combo ?? 0)) * 5, 100);
            }
        }

        return new ScoringResult(
            score: max(0, $score),
            accuracy: $accuracy,
            averageResponseMs: $averageResponseMs,
            correctCount: $correctCount,
            incorrectCount: $incorrectCount,
            missedCount: $missedCount,
            hintCount: 0,
            bestCombo: (int) ($rounds->max('combo') ?? 0),
            summary: [
                'accuracy' => $accuracy,
                'average_response_ms' => $averageResponseMs,
                'best_combo' => (int) ($rounds->max('combo') ?? 0),
                'lines' => $rounds->sum(fn (GameRound $round): int => $this->linesCleared($round)),
            ],
        );
    }

    private function linesCleared(GameRound $round): int
    {
        if (! is_array($round->response)) {
            return 0;
        }

        return max(0, min(4, (int) ($round->response['lines'] ?? 0)));
    }
}
