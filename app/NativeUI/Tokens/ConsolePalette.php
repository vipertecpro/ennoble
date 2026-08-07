<?php

namespace App\NativeUI\Tokens;

/**
 * The Stack screen's committed art direction: a neon HUD.
 *
 * WHY THIS IS NOT THEME TOKENS. Theme tokens describe roles that re-skin with
 * the app and flip between light and dark. This is the opposite: a game screen
 * that commits to ONE look regardless of the app's appearance, the way a real
 * game does. A neon console HUD rendered on a white ground is not a lighter
 * version of itself, it is a broken version — so the screen owns its palette
 * outright and the theme is not consulted.
 *
 * It still lives in one PHP home rather than being pasted through the markup,
 * for the same reason every other data-driven colour in this app does.
 */
final class ConsolePalette
{
    /** The cabinet itself. */
    public const GROUND = '#061520';

    /** The screen inside it, a shade deeper than the cabinet. */
    public const SCREEN = '#03101A';

    /** Panel outlines and rules — the HUD's structural colour. */
    public const LINE = '#22D3EE';

    /** The same line, quiet: grid, inactive edges. */
    public const LINE_DIM = '#22D3EE33';

    /** Labels: present, never loud. */
    public const LABEL = '#67E8F9';

    /** Readouts. Magenta against cyan is the whole chord of this look. */
    public const VALUE = '#FF3DAE';

    /** Where the playfield fades at top and bottom. */
    public const FADE = '#000000';

    /**
     * The pieces, pushed to neon. On a near-black screen the muted weights
     * disappear; these are chosen to glow rather than to sit politely.
     *
     * @var array<string, string>
     */
    public const PIECES = [
        'i' => '#22D3EE',
        'o' => '#FDE047',
        't' => '#E879F9',
        's' => '#4ADE80',
        'z' => '#FB7185',
        'j' => '#60A5FA',
        'l' => '#FB923C',
    ];
}
