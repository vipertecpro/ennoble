<?php

namespace App\Domain\Games\Signal;

use App\Domain\Games\Contracts\GameScoringService;
use App\Domain\Games\Contracts\ScoringResult;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use Illuminate\Support\Collection;

final class SignalScoringService implements GameScoringService
{
    /** Paid on a correct round whose rule flipped from the round before it. */
    private const SWITCH_BONUS = 25;

    /**
     * Score a Signal session from authoritative round evidence. A correct call
     * earns a flat base plus a speed bonus (the faster inside the round's
     * window, the more it pays), an unbroken-combo bonus, and a switch bonus
     * when the rule flipped from the previous round — resolving interference
     * *and* a task switch is the hardest thing Signal asks for. A wrong tap or a
     * timed-out round earns nothing.
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

        $ordered = $rounds->sortBy('round_number')->values();
        $score = 0;
        $previousRule = null;

        foreach ($ordered as $round) {
            $rule = $this->rule($round);

            if ($round->outcome === RoundOutcome::Correct) {
                $score += 100
                    + $this->speedBonus($round)
                    + min(((int) ($round->combo ?? 0)) * 10, 120)
                    + ($previousRule !== null && $rule !== null && $rule !== $previousRule ? self::SWITCH_BONUS : 0);
            }

            $previousRule = $rule;
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
     * Reward answering early in the round's window: an instant call is worth the
     * full bonus, one on the buzzer nothing.
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

    private function rule(GameRound $round): ?string
    {
        if (! is_array($round->response)) {
            return null;
        }

        $rule = $round->response['rule'] ?? null;

        return is_string($rule) && $rule !== '' ? $rule : null;
    }
}
