<?php

namespace App\Domain\Games\QuickMath;

use App\Domain\Games\Contracts\GameScoringService;
use App\Domain\Games\Contracts\ScoringResult;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use Illuminate\Support\Collection;

final class QuickMathScoringService implements GameScoringService
{
    /**
     * Calculate Quick Math accuracy, response time, combo, and score from
     * authoritative round evidence. Fast, unbroken streaks are rewarded the
     * most; wrong answers and time-outs earn nothing.
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
                + $this->speedBonus($round->response_ms)
                + min(((int) ($round->combo ?? 0)) * 12, 144),
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

    private function speedBonus(?int $responseMs): int
    {
        if ($responseMs === null) {
            return 0;
        }

        return max(0, 120 - intdiv($responseMs, 30));
    }
}
