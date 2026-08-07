<?php

namespace App\Domain\Games\Stack;

/**
 * The seven tetrominoes and their rotations.
 *
 * Each rotation is listed explicitly rather than derived by rotating a matrix
 * at runtime. Rotation of these shapes is not a plain 90° turn about the
 * centre — the O piece must not move at all, and I sits on a 4x4 grid whose
 * centre falls between cells — so a generic rotate produces pieces that drift
 * sideways as they spin. Written out, each orientation is exactly where it
 * should be and there is nothing to get subtly wrong.
 *
 * Cells are [column, row] offsets, row growing DOWNWARD to match the board.
 *
 * Colours live here because they are genuine data-driven identity — one PHP
 * home, never pasted into a view.
 */
final class StackPieces
{
    /**
     * @var array<string, array{color: string, rotations: list<list<array{0: int, 1: int}>>}>
     */
    private const PIECES = [
        'i' => [
            'color' => '#22D3EE',
            'rotations' => [
                [[0, 1], [1, 1], [2, 1], [3, 1]],
                [[2, 0], [2, 1], [2, 2], [2, 3]],
                [[0, 2], [1, 2], [2, 2], [3, 2]],
                [[1, 0], [1, 1], [1, 2], [1, 3]],
            ],
        ],
        'o' => [
            'color' => '#FACC15',
            // One orientation, repeated: an O that "rotates" visibly is the
            // classic sign of a generic rotation being applied to it.
            'rotations' => [
                [[1, 0], [2, 0], [1, 1], [2, 1]],
                [[1, 0], [2, 0], [1, 1], [2, 1]],
                [[1, 0], [2, 0], [1, 1], [2, 1]],
                [[1, 0], [2, 0], [1, 1], [2, 1]],
            ],
        ],
        't' => [
            'color' => '#C084FC',
            'rotations' => [
                [[1, 0], [0, 1], [1, 1], [2, 1]],
                [[1, 0], [1, 1], [2, 1], [1, 2]],
                [[0, 1], [1, 1], [2, 1], [1, 2]],
                [[1, 0], [0, 1], [1, 1], [1, 2]],
            ],
        ],
        's' => [
            'color' => '#4ADE80',
            'rotations' => [
                [[1, 0], [2, 0], [0, 1], [1, 1]],
                [[1, 0], [1, 1], [2, 1], [2, 2]],
                [[1, 1], [2, 1], [0, 2], [1, 2]],
                [[0, 0], [0, 1], [1, 1], [1, 2]],
            ],
        ],
        'z' => [
            'color' => '#F87171',
            'rotations' => [
                [[0, 0], [1, 0], [1, 1], [2, 1]],
                [[2, 0], [1, 1], [2, 1], [1, 2]],
                [[0, 1], [1, 1], [1, 2], [2, 2]],
                [[1, 0], [0, 1], [1, 1], [0, 2]],
            ],
        ],
        'j' => [
            'color' => '#60A5FA',
            'rotations' => [
                [[0, 0], [0, 1], [1, 1], [2, 1]],
                [[1, 0], [2, 0], [1, 1], [1, 2]],
                [[0, 1], [1, 1], [2, 1], [2, 2]],
                [[1, 0], [1, 1], [0, 2], [1, 2]],
            ],
        ],
        'l' => [
            'color' => '#FB923C',
            'rotations' => [
                [[2, 0], [0, 1], [1, 1], [2, 1]],
                [[1, 0], [1, 1], [1, 2], [2, 2]],
                [[0, 1], [1, 1], [2, 1], [0, 2]],
                [[0, 0], [1, 0], [1, 1], [1, 2]],
            ],
        ],
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::PIECES);
    }

    /**
     * The four cells of a piece in a given rotation.
     *
     * @return list<array{0: int, 1: int}>
     */
    public static function cells(string $piece, int $rotation): array
    {
        $rotations = self::PIECES[$piece]['rotations'];

        // Rotation wraps rather than being validated: callers increment it
        // forever, and a modulo here is cheaper than every caller remembering.
        return $rotations[(($rotation % 4) + 4) % 4];
    }

    public static function color(string $piece): string
    {
        return self::PIECES[$piece]['color'] ?? '#94A3B8';
    }

    public static function exists(string $piece): bool
    {
        return isset(self::PIECES[$piece]);
    }
}
