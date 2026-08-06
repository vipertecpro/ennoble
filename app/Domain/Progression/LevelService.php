<?php

namespace App\Domain\Progression;

use App\Domain\Statistics\StatisticsService;
use App\Models\Profile;
use App\Models\Statistic;

/**
 * Derives a lightweight XP / level from data the app ALREADY tracks — completed
 * sessions and correct answers on the overall {@see Statistic}. No new columns,
 * no new tracking: it is a pure read-projection surfaced in the app header and
 * on Profile as the brain-training analogue of a game's coin/level chip.
 */
final class LevelService
{
    private const XP_PER_SESSION = 25;

    private const XP_PER_CORRECT = 3;

    /** XP width of level 1; each level after widens by {@see self::LEVEL_GROWTH}. */
    private const LEVEL_BASE = 120;

    private const LEVEL_GROWTH = 60;

    public function __construct(private readonly StatisticsService $statistics) {}

    /**
     * @return array{level:int, xp:int, into:int, span:int, toNext:int, progress:float, title:string}
     */
    public function forProfile(Profile $profile): array
    {
        return $this->breakdown($this->totalXp($this->statistics->overview($profile)));
    }

    /**
     * @return array{level:int, xp:int, into:int, span:int, toNext:int, progress:float, title:string}
     */
    public function breakdown(int $xp): array
    {
        $xp = max(0, $xp);
        $level = 1;
        $start = 0;
        $span = self::LEVEL_BASE;

        while ($xp >= $start + $span) {
            $start += $span;
            $level++;
            $span = self::LEVEL_BASE + ($level - 1) * self::LEVEL_GROWTH;
        }

        $into = $xp - $start;

        return [
            'level' => $level,
            'xp' => $xp,
            'into' => $into,
            'span' => $span,
            'toNext' => $span - $into,
            'progress' => $span > 0 ? round($into / $span, 4) : 0.0,
            'title' => $this->title($level),
        ];
    }

    public function totalXp(?Statistic $statistic): int
    {
        if ($statistic === null) {
            return 0;
        }

        return (int) $statistic->sessions_completed * self::XP_PER_SESSION
            + (int) $statistic->correct_count * self::XP_PER_CORRECT;
    }

    private function title(int $level): string
    {
        return match (true) {
            $level >= 35 => 'Luminary',
            $level >= 20 => 'Sharp',
            $level >= 10 => 'Focused',
            $level >= 5 => 'Sharpening',
            default => 'Warming up',
        };
    }
}
