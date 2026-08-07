<?php

namespace App\Domain\Games\Vertex;

use App\Models\GameLevel;

/**
 * Deterministic, fully-offline object stream for Vertex.
 *
 * Vertex is a go/no-go task wearing a tunnel: one object flies out of the
 * vanishing point each round, and the player strikes it only when it matches
 * the current target form. The generator's real job is pacing that conflict —
 *
 *  - Runs of targets are LEFT ALONE on purpose. A go/no-go only bites once the
 *    player has built a reflex to strike, so the prepotent response has to be
 *    allowed to form before a decoy arrives to test it.
 *  - Runs of decoys are capped, because three passes in a row is just dead air.
 *  - The target form itself re-keys every `key_hold` rounds, so the reflex the
 *    player just built becomes the thing working against them.
 *
 * Everything is drawn from a stable seed, so a session always replays the same
 * run without any bundled content.
 */
final class VertexGenerator
{
    /** Most rounds should be strikes; that is what makes withholding hard. */
    private const DEFAULT_GO_RATIO = 0.7;

    /** Consecutive decoys allowed before one is forced to a target. */
    private const MAX_DECOY_RUN = 2;

    /**
     * Build the session's object stream.
     *
     * @return list<array{key: string, shape: string, is_go: bool}>
     */
    public function generate(GameLevel $level, string $seed, int $count): array
    {
        $config = is_array($level->configuration) ? $level->configuration : [];
        $pool = VertexShapes::pool(is_array($config['shapes'] ?? null) ? $config['shapes'] : []);
        $goRatio = $this->goRatio($config);
        $keyHold = max(1, (int) ($config['key_hold'] ?? 4));

        $rounds = [];
        $key = $pool[$this->intFromSeed($seed.':key:0', 0, count($pool) - 1)];
        $sinceKeyChange = 0;
        $decoyRun = 0;

        for ($index = 0; $index < $count; $index++) {
            if ($sinceKeyChange >= $keyHold && count($pool) > 1) {
                $key = $this->rekey($pool, $seed.':key:'.$index, $key);
                $sinceKeyChange = 0;
            }

            $isGo = $this->intFromSeed($seed.':go:'.$index, 0, 99) < (int) round($goRatio * 100);

            if (! $isGo && $decoyRun >= self::MAX_DECOY_RUN) {
                $isGo = true;
            }

            $rounds[] = [
                'key' => $key,
                'shape' => $isGo ? $key : $this->decoy($pool, $seed.':decoy:'.$index, $key),
                'is_go' => $isGo,
            ];

            $decoyRun = $isGo ? 0 : $decoyRun + 1;
            $sinceKeyChange++;
        }

        return $this->guaranteeBothOutcomes($rounds, $pool, $seed);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function goRatio(array $config): float
    {
        $ratio = (float) ($config['go_ratio'] ?? self::DEFAULT_GO_RATIO);

        // Outside this band the task stops being a go/no-go: all strikes, or so
        // few that no reflex ever forms to inhibit.
        return max(0.4, min(0.9, $ratio));
    }

    /**
     * @param  list<string>  $pool
     */
    private function rekey(array $pool, string $seed, string $current): string
    {
        $candidates = array_values(array_filter(
            $pool,
            static fn (string $shape): bool => $shape !== $current,
        ));

        if ($candidates === []) {
            return $current;
        }

        return $candidates[$this->intFromSeed($seed, 0, count($candidates) - 1)];
    }

    /**
     * @param  list<string>  $pool
     */
    private function decoy(array $pool, string $seed, string $key): string
    {
        return $this->rekey($pool, $seed, $key);
    }

    /**
     * A run with no decoy is not a go/no-go, and one with no target never lets
     * the reflex form. Any stream long enough to hold both gets both.
     *
     * @param  list<array{key: string, shape: string, is_go: bool}>  $rounds
     * @param  list<string>  $pool
     * @return list<array{key: string, shape: string, is_go: bool}>
     */
    private function guaranteeBothOutcomes(array $rounds, array $pool, string $seed): array
    {
        if (count($rounds) < 4) {
            return $rounds;
        }

        $goCount = count(array_filter($rounds, static fn (array $round): bool => $round['is_go']));

        if ($goCount === count($rounds)) {
            // Flip a late round to a decoy — by then the reflex has formed.
            $at = count($rounds) - 2;
            $rounds[$at]['is_go'] = false;
            $rounds[$at]['shape'] = $this->decoy($pool, $seed.':forced-decoy', $rounds[$at]['key']);
        } elseif ($goCount === 0) {
            $rounds[0]['is_go'] = true;
            $rounds[0]['shape'] = $rounds[0]['key'];
        }

        return $rounds;
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
