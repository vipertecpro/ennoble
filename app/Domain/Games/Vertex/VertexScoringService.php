<?php

namespace App\Domain\Games\Vertex;

use App\Domain\Games\Contracts\GameScoringService;
use App\Domain\Games\Contracts\ScoringResult;
use App\Enums\RoundOutcome;
use App\Models\GameRound;
use Illuminate\Support\Collection;

/**
 * Barrage scoring. A round is a WAVE, not a shot, because the wave is the unit
 * the player actually solves — clearing four targets out of nine while holding
 * fire on the rest is one act of search, and scoring each tap separately would
 * make a lucky sweep look like skill.
 *
 * Every wave therefore pays per confirmed hit, and then adds a clean-sweep
 * bonus only when the whole formation was resolved without a single false
 * alarm or survivor. That bonus is the point: it is worth far more to read the
 * field once and act correctly than to tap fast and absorb the errors.
 */
final class VertexScoringService implements GameScoringService
{
    private const HIT = 60;

    /** Paid only when a wave is fully cleared with no false alarms. */
    private const CLEAN_SWEEP = 120;

    private const MAX_SPEED_BONUS = 80;

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

        $timedResponses = $rounds->whereNotNull('response_ms');
        $averageResponseMs = $timedResponses->isEmpty()
            ? null
            : (int) round($timedResponses->avg('response_ms'));

        return new ScoringResult(
            score: max(0, $rounds->sum(fn (GameRound $round): int => $this->waveScore($round))),
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
     * Clearing the wave early leaves descent on the clock; that remaining
     * fraction is the speed bonus. Waves that time out earn none of it.
     */
    public static function speedBonus(float $remainingFraction): int
    {
        $fraction = max(0.0, min(1.0, $remainingFraction));

        return (int) round($fraction * self::MAX_SPEED_BONUS);
    }

    private function waveScore(GameRound $round): int
    {
        $response = is_array($round->response) ? $round->response : [];
        $hits = max(0, (int) ($response['hits'] ?? 0));
        $score = $hits * self::HIT;

        if ($round->outcome !== RoundOutcome::Correct) {
            return $score;
        }

        return $score
            + self::CLEAN_SWEEP
            + self::speedBonus((float) ($response['remaining_fraction'] ?? 0.0))
            + min(((int) ($round->combo ?? 0)) * 15, 150);
    }
}
