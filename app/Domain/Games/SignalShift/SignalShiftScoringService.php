<?php

namespace App\Domain\Games\SignalShift;

use App\Domain\Games\Contracts\GameScoringService;
use App\Domain\Games\Contracts\ScoringResult;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use Illuminate\Support\Collection;

final class SignalShiftScoringService implements GameScoringService
{
    /**
     * Calculate Signal Shift accuracy, response speed, combo, and score.
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

        $compatibleResponses = $rounds
            ->where('outcome', RoundOutcome::Correct)
            ->whereNotNull('response_ms');
        $averageResponseMs = $compatibleResponses->isEmpty()
            ? null
            : (int) round($compatibleResponses->avg('response_ms'));

        $score = $rounds->sum(function (GameRound $round): int {
            return match ($round->outcome) {
                RoundOutcome::Correct => 100
                    + $this->speedBonus($round->response_ms)
                    + min(($round->combo ?? 0) * 10, 100),
                RoundOutcome::Incorrect => -75,
                RoundOutcome::Missed => -50,
            };
        });
        $bestCombo = (int) ($rounds->max('combo') ?? 0);

        return new ScoringResult(
            score: max(0, $score),
            accuracy: $accuracy,
            averageResponseMs: $averageResponseMs,
            correctCount: $correctCount,
            incorrectCount: $incorrectCount,
            missedCount: $missedCount,
            hintCount: 0,
            bestCombo: $bestCombo,
            summary: [
                'accuracy' => $accuracy,
                'average_response_ms' => $averageResponseMs,
                'best_combo' => $bestCombo,
            ],
        );
    }

    private function speedBonus(?int $responseMs): int
    {
        if ($responseMs === null) {
            return 0;
        }

        return max(0, 100 - intdiv($responseMs, 20));
    }
}
