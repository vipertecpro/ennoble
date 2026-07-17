<?php

namespace App\Domain\Games\Contracts;

final readonly class ScoringResult
{
    /**
     * Create a normalized score result shared by both v1 games.
     *
     * @param  array<string, int|float|null>  $summary
     */
    public function __construct(
        public int $score,
        public ?float $accuracy,
        public ?int $averageResponseMs,
        public int $correctCount,
        public int $incorrectCount,
        public int $missedCount,
        public int $hintCount,
        public int $bestCombo,
        public array $summary,
    ) {}
}
