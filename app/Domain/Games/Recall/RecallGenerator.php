<?php

namespace App\Domain\Games\Recall;

use App\Models\GameLevel;

/**
 * Deterministic, fully-offline sequence generator for Recall. Each round is a
 * list of tile indices the player must reproduce; the sequence grows by one per
 * round (capped) and is derived from a stable seed, so a session always replays
 * the same sequences without any bundled content.
 */
final class RecallGenerator
{
    /**
     * Build the session's sequence set.
     *
     * @return list<array{sequence: list<int>}>
     */
    public function generate(GameLevel $level, string $seed, int $count): array
    {
        $config = is_array($level->configuration) ? $level->configuration : [];
        $tiles = max(4, (int) ($config['tiles'] ?? 9));
        $startLength = max(2, (int) ($config['start_length'] ?? 3));
        $cap = min($tiles, 9);

        $rounds = [];

        for ($index = 0; $index < $count; $index++) {
            $length = min($startLength + $index, $cap);
            $rounds[] = ['sequence' => $this->buildSequence($tiles, $length, $seed.':round:'.$index)];
        }

        return $rounds;
    }

    /**
     * @return list<int>
     */
    private function buildSequence(int $tiles, int $length, string $seed): array
    {
        $sequence = [];
        $previous = -1;

        for ($step = 0; $step < $length; $step++) {
            $tile = $this->intFromSeed($seed.':step:'.$step, 0, $tiles - 1);

            // Avoid an immediate repeat — two identical adjacent flashes read as
            // one and make the sequence ambiguous to reproduce.
            if ($tile === $previous) {
                $tile = ($tile + 1) % $tiles;
            }

            $sequence[] = $tile;
            $previous = $tile;
        }

        return $sequence;
    }

    private function intFromSeed(string $seed, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }

        $value = hexdec(substr(hash('sha1', $seed), 0, 12));

        return $min + (int) ($value % ($max - $min + 1));
    }
}
