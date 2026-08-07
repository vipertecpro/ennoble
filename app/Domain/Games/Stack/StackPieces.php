<?php

namespace App\Domain\Games\Stack;

use App\NativeUI\Tokens\ConsolePalette;

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
 * Colours come from {@see ConsolePalette}, which owns the screen's committed
 * neon art direction. The mapping from piece to colour lives here because that
 * is piece identity; the hex values live there because that is the look.
 */
final class StackPieces
{
    /**
     * @var array<string, array{color: string, rotations: list<list<array{0: int, 1: int}>>}>
     */
    private const PIECES = [
        'i' => [
            'color' => ConsolePalette::PIECES['i'],
            'rotations' => [
                [[0, 1], [1, 1], [2, 1], [3, 1]],
                [[2, 0], [2, 1], [2, 2], [2, 3]],
                [[0, 2], [1, 2], [2, 2], [3, 2]],
                [[1, 0], [1, 1], [1, 2], [1, 3]],
            ],
        ],
        'o' => [
            'color' => ConsolePalette::PIECES['o'],
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
            'color' => ConsolePalette::PIECES['t'],
            'rotations' => [
                [[1, 0], [0, 1], [1, 1], [2, 1]],
                [[1, 0], [1, 1], [2, 1], [1, 2]],
                [[0, 1], [1, 1], [2, 1], [1, 2]],
                [[1, 0], [0, 1], [1, 1], [1, 2]],
            ],
        ],
        's' => [
            'color' => ConsolePalette::PIECES['s'],
            'rotations' => [
                [[1, 0], [2, 0], [0, 1], [1, 1]],
                [[1, 0], [1, 1], [2, 1], [2, 2]],
                [[1, 1], [2, 1], [0, 2], [1, 2]],
                [[0, 0], [0, 1], [1, 1], [1, 2]],
            ],
        ],
        'z' => [
            'color' => ConsolePalette::PIECES['z'],
            'rotations' => [
                [[0, 0], [1, 0], [1, 1], [2, 1]],
                [[2, 0], [1, 1], [2, 1], [1, 2]],
                [[0, 1], [1, 1], [1, 2], [2, 2]],
                [[1, 0], [0, 1], [1, 1], [0, 2]],
            ],
        ],
        'j' => [
            'color' => ConsolePalette::PIECES['j'],
            'rotations' => [
                [[0, 0], [0, 1], [1, 1], [2, 1]],
                [[1, 0], [2, 0], [1, 1], [1, 2]],
                [[0, 1], [1, 1], [2, 1], [2, 2]],
                [[1, 0], [1, 1], [0, 2], [1, 2]],
            ],
        ],
        'l' => [
            'color' => ConsolePalette::PIECES['l'],
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
