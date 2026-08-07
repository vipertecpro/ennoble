<?php

namespace App\Domain\Games\Axis;

/**
 * The 24 orientations a solid can be turned into without reflecting it.
 *
 * This is the whole basis of the game: two figures are "the same shape" exactly
 * when one of these 24 rotations maps one onto the other. A mirror image is NOT
 * among them, which is why a mirrored figure can never be rotated into its
 * original — that impossibility is the puzzle.
 *
 * The set is derived rather than typed out. Of the 48 signed axis permutations,
 * the 24 with determinant +1 are rotations and the 24 with determinant -1 are
 * reflections; hand-listing them is the classic place to accidentally include a
 * reflection, which would make some rounds unanswerable.
 */
final class AxisRotations
{
    /** @var list<array{0: array{0: int, 1: int, 2: int}, 1: array{0: int, 1: int, 2: int}, 2: array{0: int, 1: int, 2: int}}>|null */
    private static ?array $cache = null;

    /**
     * @return list<array{0: array{0: int, 1: int, 2: int}, 1: array{0: int, 1: int, 2: int}, 2: array{0: int, 1: int, 2: int}}>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $matrices = [];

        // Every way to send the three axes to three distinct axes...
        foreach ([[0, 1, 2], [0, 2, 1], [1, 0, 2], [1, 2, 0], [2, 0, 1], [2, 1, 0]] as $permutation) {
            // ...with every combination of signs.
            foreach ([1, -1] as $signX) {
                foreach ([1, -1] as $signY) {
                    foreach ([1, -1] as $signZ) {
                        $signs = [$signX, $signY, $signZ];
                        $matrix = [[0, 0, 0], [0, 0, 0], [0, 0, 0]];

                        foreach ($permutation as $row => $column) {
                            $matrix[$row][$column] = $signs[$row];
                        }

                        if (self::determinant($matrix) === 1) {
                            $matrices[] = $matrix;
                        }
                    }
                }
            }
        }

        return self::$cache = $matrices;
    }

    /**
     * @param  array{0: array{0: int, 1: int, 2: int}, 1: array{0: int, 1: int, 2: int}, 2: array{0: int, 1: int, 2: int}}  $m
     */
    private static function determinant(array $m): int
    {
        return $m[0][0] * ($m[1][1] * $m[2][2] - $m[1][2] * $m[2][1])
            - $m[0][1] * ($m[1][0] * $m[2][2] - $m[1][2] * $m[2][0])
            + $m[0][2] * ($m[1][0] * $m[2][1] - $m[1][1] * $m[2][0]);
    }
}
