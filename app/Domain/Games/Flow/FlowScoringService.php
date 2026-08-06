<?php

namespace App\Domain\Games\Flow;

use App\Domain\Games\Contracts\GameScoringService;
use App\Domain\Games\Contracts\ScoringResult;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use Illuminate\Support\Collection;

final class FlowScoringService implements GameScoringService
{
    /**
     * Score a Flow session from authoritative round evidence. A matched current
     * earns a flat base plus a speed bonus (the earlier in its window the swipe
     * lands, the more it pays) and an unbroken-combo bonus; a wrong swipe or a
     * missed current earns nothing.
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

        $score = $rounds->sum(fn (GameRound $round): int => match ($round->outcome) {
            RoundOutcome::Correct => 100
                + $this->speedBonus($round)
                + min(((int) ($round->combo ?? 0)) * 10, 120),
            RoundOutcome::Incorrect, RoundOutcome::Missed => 0,
        });

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
     * Reward reacting early in the current's window: a swipe on the first frame
     * is worth the full bonus, one on the deadline nothing.
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
}
