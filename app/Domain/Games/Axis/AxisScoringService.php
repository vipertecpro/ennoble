<?php

namespace App\Domain\Games\Axis;

use App\Domain\Games\Contracts\GameScoringService;
use App\Domain\Games\Contracts\ScoringResult;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use Illuminate\Support\Collection;

final class AxisScoringService implements GameScoringService
{
    /**
     * Paid per cube beyond the smallest figure. Rotating a seven-cube solid in
     * your head is genuinely harder than a four-cube one, and response time
     * rises with it — without this, the easy rounds would pay best simply for
     * being quick.
     */
    private const COMPLEXITY_BONUS_PER_CUBE = 12;

    private const BASELINE_CUBES = 4;

    /**
     * Score an Axis session from authoritative round evidence. A correct call
     * earns a flat base, a speed bonus scaled to the round's window, an
     * unbroken-combo bonus, and a complexity bonus for the size of the figure
     * that was rotated. A wrong call or a timed-out round earns nothing.
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
                + $this->speedBonus($round)
                + min(((int) ($round->combo ?? 0)) * 10, 120)
                + $this->complexityBonus($round);
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

    /**
     * Reward answering early in the round's window: an instant call is worth
     * the full bonus, one on the buzzer nothing.
     */
    private function speedBonus(GameRound $round): int
    {
        $windowMs = is_array($round->response) ? (int) ($round->response['window_ms'] ?? 0) : 0;
        $responseMs = (int) ($round->response_ms ?? 0);

        if ($windowMs <= 0 || $responseMs <= 0) {
            return 0;
        }

        $fraction = 1 - min(1.0, $responseMs / $windowMs);

        return max(0, min(50, (int) round($fraction * 50)));
    }

    private function complexityBonus(GameRound $round): int
    {
        $cubes = is_array($round->response) ? (int) ($round->response['cubes'] ?? 0) : 0;

        if ($cubes <= self::BASELINE_CUBES) {
            return 0;
        }

        return ($cubes - self::BASELINE_CUBES) * self::COMPLEXITY_BONUS_PER_CUBE;
    }
}
