<?php

namespace App\Domain\Games\Flow;

use App\Models\GameLevel;

/**
 * Deterministic, fully-offline current generator for Flow. Each round is a
 * single swipe direction the player must match as the current surges in. The
 * allowed directions widen with difficulty and are drawn from a stable seed, so
 * a session always replays the same run without any bundled content.
 */
final class FlowGenerator
{
    /** The full directional vocabulary; a level opts into a subset. */
    private const DIRECTIONS = ['left', 'right', 'up', 'down'];

    /**
     * Build the session's current set.
     *
     * @return list<array{direction: string}>
     */
    public function generate(GameLevel $level, string $seed, int $count): array
    {
        $config = is_array($level->configuration) ? $level->configuration : [];
        $directions = $this->directions($config);

        $rounds = [];
        $previous = null;
        $beforePrevious = null;

        for ($index = 0; $index < $count; $index++) {
            $direction = $this->pickDirection(
                $directions,
                $seed.':round:'.$index,
                $previous,
                $beforePrevious,
            );

            $rounds[] = ['direction' => $direction];
            $beforePrevious = $previous;
            $previous = $direction;
        }

        return $rounds;
    }

    /**
     * The active, validated direction pool for this level.
     *
     * @param  array<string, mixed>  $config
     * @return list<string>
     */
    private function directions(array $config): array
    {
        $configured = is_array($config['directions'] ?? null) ? $config['directions'] : [];
        $allowed = array_values(array_filter(
            $configured,
            static fn (mixed $direction): bool => in_array($direction, self::DIRECTIONS, true),
        ));

        return $allowed !== [] ? $allowed : ['left', 'right'];
    }

    /**
     * @param  list<string>  $directions
     */
    private function pickDirection(array $directions, string $seed, ?string $previous, ?string $beforePrevious): string
    {
        $index = $this->intFromSeed($seed, 0, count($directions) - 1);
        $direction = $directions[$index];

        // Avoid three identical surges in a row — a run of the same swipe reads
        // as a stutter rather than a rhythm to react to.
        if (count($directions) > 1 && $direction === $previous && $previous === $beforePrevious) {
            $direction = $directions[($index + 1) % count($directions)];
        }

        return $direction;
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
