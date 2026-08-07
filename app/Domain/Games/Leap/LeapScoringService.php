<?php

namespace App\Domain\Games\Leap;

use App\Domain\Games\Contracts\GameScoringService;
use App\Domain\Games\Contracts\ScoringResult;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use Illuminate\Support\Collection;

final class LeapScoringService implements GameScoringService
{
    /**
     * Paid for clearing a fast obstacle. Speed is the difficulty in a runner,
     * so the score has to track it — otherwise a long slow opening is worth
     * exactly as much as the frantic end of a run.
     */
    private const MAX_PACE_BONUS = 60;

    /** Above this the obstacle is at its opening pace and earns no bonus. */
    private const SLOW_TRAVEL_MS = 2600;

    private const FAST_TRAVEL_MS = 1200;

    /**
     * Score a Leap run from authoritative round evidence: a flat base per
     * obstacle cleared, a pace bonus for how fast it was closing, and an
     * unbroken-combo bonus. A collision earns nothing.
     *
     * There is no time bonus, because there is no clock — the run ends when
     * the course does or the lives do.
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
            if ($round->outcome !== RoundOutcome::Correct) {
                continue;
            }

            $score += 100
                + $this->paceBonus($round)
                + min(((int) ($round->combo ?? 0)) * 10, 150);
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
            ],
        );
    }

    private function paceBonus(GameRound $round): int
    {
        $travelMs = is_array($round->response) ? (int) ($round->response['travel_ms'] ?? 0) : 0;

        if ($travelMs <= 0) {
            return 0;
        }

        $fraction = (self::SLOW_TRAVEL_MS - $travelMs) / (self::SLOW_TRAVEL_MS - self::FAST_TRAVEL_MS);

        return max(0, min(self::MAX_PACE_BONUS, (int) round($fraction * self::MAX_PACE_BONUS)));
    }
}
