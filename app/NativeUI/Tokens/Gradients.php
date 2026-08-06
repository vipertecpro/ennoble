<?php

namespace App\NativeUI\Tokens;

use Native\Mobile\Facades\System;

/**
 * Gradient vocabulary — the single home for every gradient recipe.
 *
 * Gradients cannot use `from-theme-*` stops: the EDGE parser resolves a theme
 * token in a gradient stop against the LIGHT palette only, baking the light
 * value into both modes. So the neon gradients are authored as Tailwind palette
 * literals here (which DO adapt when paired with `to-transparent`, because the
 * transparent stop lets the theme background show through). Centralising them
 * means the hues live in exactly one place instead of being copy-pasted per
 * view.
 */
final class Gradients
{
    /** The glossy primary CTA fill (lime → cyan). Pair with black ink text. */
    public const CTA = 'bg-linear-to-r from-lime-400 to-cyan-400';

    /**
     * Per-game identity hues: [from, to] Tailwind palette names.
     *
     * @var array<string, array{0:string,1:string}>
     */
    private const GAME = [
        'word-match' => ['lime-400', 'emerald-500'],
        'quick-math' => ['cyan-400', 'sky-500'],
        'recall' => ['violet-500', 'fuchsia-500'],
        'flow' => ['indigo-500', 'cyan-400'],
    ];

    private const FALLBACK = ['lime-400', 'cyan-500'];

    /**
     * Generic theme-adaptive glass fill from two palette hues
     * (accent/25 → accent2/10 → transparent).
     */
    public static function glass(string $from = 'lime-400', string $to = 'cyan-500'): string
    {
        return "bg-linear-to-br from-{$from}/25 via-{$to}/10 to-transparent";
    }

    /** Glass fill tinted to a game's identity. */
    public static function gameGlass(string $slug): string
    {
        [$from, $to] = self::GAME[$slug] ?? self::FALLBACK;

        return self::glass($from, $to);
    }

    /** Glowing border class matched to a game's identity. */
    public static function gameBorder(string $slug): string
    {
        [$from] = self::GAME[$slug] ?? self::FALLBACK;

        return "border-{$from}/45";
    }

    /** Solid identity gradient (for icon chips / accents). */
    public static function gameSolid(string $slug): string
    {
        [$from, $to] = self::GAME[$slug] ?? self::FALLBACK;

        return "bg-linear-to-br from-{$from} to-{$to}";
    }

    /** The primary hue name for a game (for single-color borders / text). */
    public static function gameHue(string $slug): string
    {
        return (self::GAME[$slug] ?? self::FALLBACK)[0];
    }

    /**
     * The subtle full-screen background gradient (depth, not flat fill). Chosen
     * by live appearance because theme-token gradient stops bake the light value.
     * Apply on a screen's root column instead of `bg-theme-background`.
     */
    public static function screen(): string
    {
        return System::appearance() === 'dark'
            ? 'bg-linear-to-b from-[#27272F] via-[#212121] to-[#1B1B1B]'
            : 'bg-linear-to-b from-[#EDF0F8] via-[#FFFFFF] to-[#F6F7FB]';
    }
}
