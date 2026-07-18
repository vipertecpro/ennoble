<?php

namespace App\Domain\Games\ClearThought;

use App\Domain\Games\Contracts\GameScoringService;
use App\Domain\Games\Contracts\ScoringResult;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use Illuminate\Support\Collection;

final class ClearThoughtScoringService implements GameScoringService
{
    /**
     * Calculate Clear Thought correctness, hint use, attempts, time, and score.
     *
     * @param  Collection<int, GameRound>  $rounds
     */
    public function score(Collection $rounds): ScoringResult
    {
        $correctCount = $rounds->where('outcome', RoundOutcome::Correct)->count();
        $incorrectCount = $rounds->where('outcome', RoundOutcome::Incorrect)->count();
        $missedCount = $rounds->where('outcome', RoundOutcome::Missed)->count();
        $attemptedCount = $correctCount + $incorrectCount + $missedCount;
        $hintCount = $rounds->where('hint_used', true)->count();
        $accuracy = $attemptedCount === 0
            ? null
            : round(($correctCount / $attemptedCount) * 100, 2);

        $compatibleResponses = $rounds->whereNotNull('response_ms');
        $averageResponseMs = $compatibleResponses->isEmpty()
            ? null
            : (int) round($compatibleResponses->avg('response_ms'));

        $score = $rounds->sum(function (GameRound $round): int {
            $attempts = max(1, (int) data_get($round->response, 'attempts', 1));
            $attemptPenalty = ($attempts - 1) * 25;

            return match ($round->outcome) {
                RoundOutcome::Correct => max(0, 200
                    + $this->speedBonus($round->response_ms)
                    - ($round->hint_used ? 50 : 0)
                    - $attemptPenalty),
                RoundOutcome::Incorrect => -25,
                RoundOutcome::Missed => 0,
            };
        });

        return new ScoringResult(
            score: max(0, $score),
            accuracy: $accuracy,
            averageResponseMs: $averageResponseMs,
            correctCount: $correctCount,
            incorrectCount: $incorrectCount,
            missedCount: $missedCount,
            hintCount: $hintCount,
            bestCombo: 0,
            summary: [
                'accuracy' => $accuracy,
                'average_response_ms' => $averageResponseMs,
                'hints_used' => $hintCount,
            ],
        );
    }

    private function speedBonus(?int $responseMs): int
    {
        if ($responseMs === null) {
            return 0;
        }

        return max(0, 100 - intdiv($responseMs, 50));
    }
}
