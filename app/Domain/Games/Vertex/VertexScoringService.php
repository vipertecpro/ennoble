<?php

namespace App\Domain\Games\Vertex;

use App\Domain\Games\Contracts\GameScoringService;
use App\Domain\Games\Contracts\ScoringResult;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use Illuminate\Support\Collection;

/**
 * Vertex scoring. Two different things count as Correct here — striking a
 * target, and correctly letting a decoy pass — so they are paid differently:
 * a strike is the skilled act and carries a depth bonus, a clean pass is the
 * disciplined one and pays a flat, smaller amount. Both build combo, because
 * holding a go/no-go together is the whole game.
 */
final class VertexScoringService implements GameScoringService
{
    /** Landed a strike on a target. */
    private const STRIKE_BASE = 100;

    /** Let a decoy fly past untouched. */
    private const PASS_BASE = 30;

    /** Depth at which a strike is worth full bonus (0 = spawn, 1 = arrival). */
    public const SWEET_SPOT = 0.68;

    /** How far either side of the sweet spot still pays anything. */
    public const SWEET_SPOT_TOLERANCE = 0.32;

    private const MAX_DEPTH_BONUS = 60;

    /**
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

        // Only struck rounds have a response time; a clean pass has none, and
        // averaging zeros in would make discipline look like slowness.
        $timedResponses = $rounds->whereNotNull('response_ms');
        $averageResponseMs = $timedResponses->isEmpty()
            ? null
            : (int) round($timedResponses->avg('response_ms'));

        $score = $rounds->sum(fn (GameRound $round): int => $this->roundScore($round));

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
            ],
        );
    }

    /**
     * The depth bonus for a strike: full value at the strike ring, tapering to
     * nothing as the object is caught too early (a stab in the dark) or too
     * late (riding the deadline).
     */
    public static function depthBonus(float $depth): int
    {
        $offset = abs($depth - self::SWEET_SPOT);
        $fraction = 1 - ($offset / self::SWEET_SPOT_TOLERANCE);

        return max(0, min(self::MAX_DEPTH_BONUS, (int) round($fraction * self::MAX_DEPTH_BONUS)));
    }

    private function roundScore(GameRound $round): int
    {
        if ($round->outcome !== RoundOutcome::Correct) {
            return 0;
        }

        $response = is_array($round->response) ? $round->response : [];
        $combo = (int) ($round->combo ?? 0);

        if (($response['is_go'] ?? false) === true) {
            return self::STRIKE_BASE
                + self::depthBonus((float) ($response['depth'] ?? 0.0))
                + min($combo * 10, 120);
        }

        return self::PASS_BASE + min($combo * 10, 60);
    }
}
