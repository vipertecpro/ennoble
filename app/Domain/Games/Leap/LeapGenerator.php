<?php

namespace App\Domain\Games\Leap;

use App\Models\GameLevel;
use App\NativeComponents\Screens\LeapGame;

/**
 * Deterministic, fully-offline course generator for Leap.
 *
 * A course is a list of obstacles, each with the gap before it and how fast it
 * closes. Speed ramps across the run, so the same jump that was comfortable at
 * the start is late by the end — that ramp is the entire difficulty curve, and
 * it is data rather than a hand-tuned schedule.
 *
 * THE GAP FLOOR IS LOAD-BEARING. A jump keeps the runner airborne for a fixed
 * window; if two obstacles arrive inside that window, one jump clears both and
 * the player is rewarded for a mistake. Every gap is kept above that window
 * plus a margin — see {@see self::MINIMUM_GAP_MS}.
 */
final class LeapGenerator
{
    /**
     * Must stay comfortably above the airborne window in
     * {@see LeapGame::CLEAR_WINDOW_END_MS}, or a
     * single jump starts covering two obstacles.
     */
    private const MINIMUM_GAP_MS = 900;

    /**
     * @return list<array{gap_ms: int, travel_ms: int, height: int}>
     */
    public function generate(GameLevel $level, string $seed, int $count): array
    {
        $config = is_array($level->configuration) ? $level->configuration : [];

        $startTravelMs = max(1200, (int) ($config['travel_ms'] ?? 2600));
        $endTravelMs = max(900, min((int) ($config['min_travel_ms'] ?? 1700), $startTravelMs));
        $maxHeight = max(1, min((int) ($config['max_height'] ?? 2), 3));

        $obstacles = [];

        for ($index = 0; $index < $count; $index++) {
            // Linear ramp from the opening speed to the closing one, so the
            // last obstacle of a short course is as fast as the last of a long
            // one — difficulty tracks progress, not wall-clock.
            $progress = $count <= 1 ? 1.0 : $index / ($count - 1);
            $travelMs = (int) round($startTravelMs + ($endTravelMs - $startTravelMs) * $progress);

            // The gap shrinks with speed too, but never past the floor.
            $spread = $this->intFromSeed($seed.':gap:'.$index, 0, 700);
            $gapMs = max(self::MINIMUM_GAP_MS, (int) round($travelMs * 0.55) + $spread);

            $obstacles[] = [
                'gap_ms' => $index === 0 ? 1400 : $gapMs,
                'travel_ms' => $travelMs,
                // Taller obstacles read as more urgent but do not change the
                // jump — the runner clears any of them. Height is texture, not
                // a second mechanic to learn.
                'height' => 1 + $this->intFromSeed($seed.':height:'.$index, 0, $maxHeight - 1),
            ];
        }

        return $obstacles;
    }

    private function intFromSeed(string $seed, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }

        return $min + (int) (hexdec(substr(hash('sha256', $seed), 0, 8)) % ($max - $min + 1));
    }
}
