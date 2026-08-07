<?php

namespace App\NativeUI\Tokens;

/**
 * The Stack screen's HUD palette.
 *
 * These ARE theme tokens — `console-*` in config/native-ui.php — read through
 * the appearance-aware `theme()` helper, so the console has a light cabinet on
 * a light device and a dark one on a dark device. The look is the same
 * instrument in both: cyan structure, magenta readouts, faint grid. Only the
 * ground and the ink swap.
 *
 * This class exists so the mapping from "what the HUD calls it" to "which
 * token" lives in one place, rather than every view knowing token names.
 *
 * The PIECES are deliberately NOT appearance-aware. A tetromino's colour is
 * its identity — an I piece is cyan in every version of this game ever made —
 * and re-tinting it per appearance would make the same piece look like a
 * different one. They are chosen at a saturation that holds on both grounds.
 */
final class ConsolePalette
{
    /** @var array<string, string> */
    public const PIECES = [
        'i' => '#06B6D4',
        'o' => '#EAB308',
        't' => '#A855F7',
        's' => '#22C55E',
        'z' => '#EF4444',
        'j' => '#3B82F6',
        'l' => '#F97316',
    ];

    /** The cabinet. */
    public static function ground(): string
    {
        return theme('console-ground');
    }

    /** The screen inside it. */
    public static function screen(): string
    {
        return theme('console-screen');
    }

    /** Panel outlines and the frame — the HUD's structural colour. */
    public static function line(): string
    {
        return theme('console-line');
    }

    /** The same line, quiet: the grid, which should be barely there. */
    public static function lineDim(): string
    {
        return theme('console-line-dim');
    }

    public static function label(): string
    {
        return theme('console-label');
    }

    /** Readouts. Magenta against cyan is the chord this look is built on. */
    public static function value(): string
    {
        return theme('console-value');
    }

    /** Where the playfield falls away at top and bottom. */
    public static function fade(): string
    {
        return theme('console-fade');
    }
}
