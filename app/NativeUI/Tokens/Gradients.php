<?php

namespace App\NativeUI\Tokens;

use Native\Mobile\Facades\System;

/**
 * Gradient vocabulary — a thin reader over the `gradients` block in
 * config/native-ui.php, which is the single source of truth for every hue.
 *
 * EDGE gradient STOPS must be Tailwind palette names (from-lime-400): arbitrary
 * #hex stops do not parse, and theme-token stops bake the light value into both
 * modes. So the config authors hues as palette names; this class expands them
 * into the glass / border / solid / screen / hairline recipes the views consume.
 */
final class Gradients
{
    /**
     * The glossy primary CTA fill (lime → cyan). Pair with black ink text.
     */
    public static function cta(): string
    {
        return config('native-ui.gradients.cta', 'bg-linear-to-r from-lime-400 to-cyan-400');
    }

    /**
     * Generic theme-adaptive glass fill from two palette hues
     * (from/25 → via/10 → transparent). Defaults to the config fallback pair.
     */
    public static function glass(?string $from = null, ?string $to = null): string
    {
        [$fallbackFrom, $fallbackTo] = self::fallback();
        $from ??= $fallbackFrom;
        $to ??= $fallbackTo;

        return "bg-linear-to-br from-{$from}/25 via-{$to}/10 to-transparent";
    }

    /** Glass fill tinted to a game's identity. */
    public static function gameGlass(string $slug): string
    {
        [$from, $to] = self::gameHues($slug);

        return self::glass($from, $to);
    }

    /** Glowing border class matched to a game's identity. */
    public static function gameBorder(string $slug): string
    {
        [$from] = self::gameHues($slug);

        return "border-{$from}/45";
    }

    /** Solid identity gradient (for icon chips / accents). */
    public static function gameSolid(string $slug): string
    {
        [$from, $to] = self::gameHues($slug);

        return "bg-linear-to-br from-{$from} to-{$to}";
    }

    /** The primary hue name for a game (for single-color borders / text). */
    public static function gameHue(string $slug): string
    {
        return self::gameHues($slug)[0];
    }

    /**
     * [from, via] hues for an onboarding step tone, from config.
     *
     * @return array{0:string,1:string}
     */
    public static function onboardingTone(string $tone): array
    {
        $tones = config('native-ui.gradients.onboarding_tones', []);

        return $tones[$tone] ?? ($tones['lime'] ?? ['lime-400', 'emerald-500']);
    }

    /**
     * The full-screen background gradient (depth, not flat fill). Chosen by live
     * appearance because theme-token gradient stops bake the light value. Apply
     * on a screen's root column instead of `bg-theme-background`.
     */
    public static function screen(): string
    {
        // Solid canvas (auto light/dark) so the screen, top bar, and tab bar all
        // share one tone and the chrome merges seamlessly into the content.
        // Cards use `surface-elevated`, which is one step off `canvas`, so they
        // still read as raised without a gradient fighting the chrome.
        return 'bg-theme-canvas';
    }

    /**
     * The single, consistent hairline border for utility surfaces (pills, chips,
     * cards, list containers) — use everywhere instead of `border-theme-border`
     * so borders stay in sync.
     */
    public static function hairline(): string
    {
        return self::forAppearance('hairline', 'border-white/8', 'border-black/8');
    }

    /**
     * Read a per-appearance gradient class from config, falling back to a literal.
     */
    private static function forAppearance(string $key, string $darkDefault, string $lightDefault): string
    {
        $appearance = System::appearance() === 'dark' ? 'dark' : 'light';

        return config(
            "native-ui.gradients.{$key}.{$appearance}",
            $appearance === 'dark' ? $darkDefault : $lightDefault,
        );
    }

    /**
     * [from, to] hues for a game, from config (falls back to the shared pair).
     *
     * @return array{0:string,1:string}
     */
    private static function gameHues(string $slug): array
    {
        $games = config('native-ui.gradients.games', []);

        return $games[$slug] ?? self::fallback();
    }

    /**
     * @return array{0:string,1:string}
     */
    private static function fallback(): array
    {
        return config('native-ui.gradients.fallback', ['lime-400', 'cyan-500']);
    }
}
