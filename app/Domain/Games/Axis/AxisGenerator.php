<?php

namespace App\Domain\Games\Axis;

use App\Models\GameLevel;

/**
 * Deterministic, fully-offline round generator for Axis.
 *
 * Each round is a Shepard–Metzler mental-rotation trial: one reference figure
 * and two candidates. One candidate is the reference under some rotation; the
 * other is its MIRROR image, also rotated. Only one can be turned into the
 * reference, and no amount of turning reaches the other — the player has to
 * rotate it in their head to tell which.
 *
 * The generator's real job is guaranteeing the round HAS an answer. A figure
 * with a mirror plane is congruent to its own reflection, so both candidates
 * would be correct; those are rejected and redrawn rather than shipped. See
 * {@see AxisFigure::isChiral()}.
 *
 * Everything derives from a stable seed, so a session replays identically with
 * no bundled content.
 */
final class AxisGenerator
{
    /** Beyond this, a self-avoiding walk starts failing to find anywhere to go. */
    private const MAX_WALK_ATTEMPTS = 40;

    /** A chiral figure is common; this is a safety net, not an expected path. */
    private const MAX_FIGURE_ATTEMPTS = 60;

    private const DIRECTIONS = [
        [1, 0, 0], [-1, 0, 0],
        [0, 1, 0], [0, -1, 0],
        [0, 0, 1], [0, 0, -1],
    ];

    /**
     * @return list<array{
     *     cells: list<array{0: int, 1: int, 2: int}>,
     *     matchCells: list<array{0: int, 1: int, 2: int}>,
     *     decoyCells: list<array{0: int, 1: int, 2: int}>,
     *     answer: string
     * }>
     */
    public function generate(GameLevel $level, string $seed, int $count): array
    {
        $config = is_array($level->configuration) ? $level->configuration : [];
        $cubes = max(4, min((int) ($config['cubes'] ?? 6), 9));

        $rounds = [];

        for ($index = 0; $index < $count; $index++) {
            $figure = $this->chiralFigure($seed.':figure:'.$index, $cubes);

            $rotations = AxisRotations::all();

            // The match is rotated so it cannot be recognised by position, and
            // the decoy is rotated independently so the two are not trivially
            // told apart by their pose rather than their shape.
            $match = $figure->rotated($rotations[$this->intFromSeed($seed.':match:'.$index, 0, count($rotations) - 1)]);
            $decoy = $figure->mirrored()->rotated($rotations[$this->intFromSeed($seed.':decoy:'.$index, 0, count($rotations) - 1)]);

            $answer = $this->intFromSeed($seed.':side:'.$index, 0, 1) === 0 ? 'a' : 'b';

            $rounds[] = [
                'cells' => $figure->cells,
                'matchCells' => $match->cells,
                'decoyCells' => $decoy->cells,
                'answer' => $answer,
            ];
        }

        return $rounds;
    }

    /** Draw figures until one differs from its own mirror image. */
    private function chiralFigure(string $seed, int $cubes): AxisFigure
    {
        for ($attempt = 0; $attempt < self::MAX_FIGURE_ATTEMPTS; $attempt++) {
            $figure = $this->walk($seed.':'.$attempt, $cubes);

            if ($figure->isChiral()) {
                return $figure;
            }
        }

        // Reached only if a seed is pathologically unlucky. An L-tetromino-like
        // fallback that is known chiral beats returning an unanswerable round.
        return new AxisFigure([[0, 0, 0], [1, 0, 0], [2, 0, 0], [2, 1, 0], [2, 1, 1]]);
    }

    /**
     * A self-avoiding walk on the lattice. Turning is forced every few steps —
     * a straight run is achiral and mostly gets thrown away, so biasing away
     * from it makes generation converge much faster.
     */
    private function walk(string $seed, int $cubes): AxisFigure
    {
        $cells = [[0, 0, 0]];
        $occupied = ['0,0,0' => true];
        $current = [0, 0, 0];
        $lastDirection = null;

        while (count($cells) < $cubes) {
            $placed = false;

            for ($attempt = 0; $attempt < self::MAX_WALK_ATTEMPTS; $attempt++) {
                $step = self::DIRECTIONS[$this->intFromSeed(
                    $seed.':step:'.count($cells).':'.$attempt,
                    0,
                    count(self::DIRECTIONS) - 1,
                )];

                // Never double back, and prefer a genuine turn.
                if ($lastDirection !== null && $step === $lastDirection && $attempt < self::MAX_WALK_ATTEMPTS - 8) {
                    continue;
                }

                $next = [$current[0] + $step[0], $current[1] + $step[1], $current[2] + $step[2]];
                $key = implode(',', $next);

                if (isset($occupied[$key])) {
                    continue;
                }

                $cells[] = $next;
                $occupied[$key] = true;
                $current = $next;
                $lastDirection = $step;
                $placed = true;
                break;
            }

            // Boxed in: keep the shorter figure rather than loop forever.
            if (! $placed) {
                break;
            }
        }

        return new AxisFigure($cells);
    }

    private function intFromSeed(string $seed, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }

        return $min + (int) (hexdec(substr(hash('sha256', $seed), 0, 8)) % ($max - $min + 1));
    }
}
