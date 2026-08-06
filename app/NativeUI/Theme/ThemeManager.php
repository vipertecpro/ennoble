<?php

namespace App\NativeUI\Theme;

use App\Domain\Profile\ProfileService;
use App\Enums\ThemePreference;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Native\Mobile\Edge\TailwindParser;
use Native\Mobile\Facades\System;
use Native\Mobile\UI\Theme;

final class ThemeManager
{
    /**
     * Immutable snapshot of the app's configured theme palette.
     *
     * @var array<string, mixed>|null
     */
    private static ?array $baseTheme = null;

    public function __construct(private readonly ProfileService $profiles) {}

    /**
     * The appearance the player has chosen (System / Light / Dark), read from
     * their saved settings. `tokensFor()` sets the `color-scheme` key so a
     * forced Light/Dark also drives the platform chrome (status bar, system
     * inks), not just the palette — following the device remains the default.
     */
    public function currentPreference(): ThemePreference
    {
        // The app follows the device appearance — there is no in-app override.
        // Forcing Light/Dark only swaps the palette tokens; it can't reach the
        // native controls' SwiftUI environment colorScheme through the bounded
        // EDGE chrome, so forced modes left native text mis-coloured. Following
        // the device keeps every surface and control coherent, straight from
        // the config's light/dark blocks.
        return ThemePreference::System;
    }

    /**
     * Apply the current local preference to the Native UI token store.
     */
    public function applyCurrent(): ThemePreference
    {
        return $this->apply($this->currentPreference());
    }

    /**
     * Apply system-aware or explicitly forced semantic token palettes.
     */
    public function apply(ThemePreference $preference): ThemePreference
    {
        Theme::load($this->tokensFor($preference));
        TailwindParser::clearCache();

        return $preference;
    }

    /**
     * Apply the base theme with a per-game accent override merged into both the
     * light and dark palettes. Scoped by convention: every other screen re-applies
     * the base theme in its own mount()/onResume(), so the override never leaks —
     * a game screen calls this in mount() to give itself a distinct colour.
     *
     * @param  array<string, string>  $accentTokens
     */
    public function applyWithAccent(array $accentTokens): ThemePreference
    {
        $preference = $this->currentPreference();
        $tokens = $this->tokensFor($preference);

        foreach (['light', 'dark'] as $mode) {
            if (isset($tokens[$mode]) && is_array($tokens[$mode])) {
                $tokens[$mode] = [...$tokens[$mode], ...$accentTokens];
            }
        }

        Theme::load($tokens);
        TailwindParser::clearCache();

        return $preference;
    }

    /**
     * Resolve the effective appearance for a preference.
     */
    public function appearance(
        ThemePreference $preference,
        ?string $systemAppearance = null,
    ): string {
        if ($preference !== ThemePreference::System) {
            return $preference->value;
        }

        $appearance = $systemAppearance ?? System::appearance();

        return $appearance === ThemePreference::Dark->value
            ? ThemePreference::Dark->value
            : ThemePreference::Light->value;
    }

    /**
     * Resolve an effective semantic color token.
     */
    public function color(
        string $token,
        ?ThemePreference $preference = null,
        ?string $systemAppearance = null,
    ): string {
        $preference ??= $this->currentPreference();
        $appearance = $this->appearance($preference, $systemAppearance);
        $value = config("native-ui.theme.{$appearance}.{$token}");

        return is_string($value) ? $value : '#000000';
    }

    /**
     * Determine whether the current profile requests reduced motion.
     */
    public function prefersReducedMotion(): bool
    {
        return (bool) ($this->profiles->current()?->setting?->reduced_motion ?? false);
    }

    /**
     * Resolve a duration, reducing non-essential motion to zero when requested.
     */
    public function motionDuration(MotionToken $token): int
    {
        if ($this->prefersReducedMotion()) {
            return 0;
        }

        return DesignTokens::motionDuration($token);
    }

    /**
     * The app's own theme palette from `config/native-ui.php`, snapshotted once
     * and kept immutable for the process.
     *
     * `Native\Mobile\UI\Theme::load()` writes the *effective* palette back into
     * `config('native-ui.theme.light|dark')` on every call (its `syncConfig`).
     * So after a forced Light/Dark apply, re-reading the live config would feed
     * that already-collapsed palette back into `tokensFor()` and the next
     * appearance switch would resolve the wrong colours. Deriving every payload
     * from this pristine snapshot breaks that feedback loop.
     *
     * @return array<string, mixed>
     */
    private static function baseTheme(): array
    {
        if (self::$baseTheme === null) {
            $configured = config('native-ui.theme', []);
            self::$baseTheme = is_array($configured) ? $configured : [];
        }

        return self::$baseTheme;
    }

    /**
     * Build the token payload for a preference, including the top-level
     * `color-scheme` key ('light'|'dark'|'system') the native shells use to
     * force the PLATFORM color scheme via preferredColorScheme. Palette slots
     * alone cannot reach SwiftUI system chrome (toggle off-tracks, default
     * label inks, the keyboard) — those style from the environment
     * colorScheme, which follows the OS unless explicitly forced.
     *
     * @return array<string, mixed>
     */
    private function tokensFor(ThemePreference $preference): array
    {
        $configured = $this->baseTheme();

        if (! is_array($configured)) {
            return [];
        }

        if ($preference === ThemePreference::System) {
            $configured['color-scheme'] = 'system';

            return $configured;
        }

        $configured['color-scheme'] = $preference->value;

        $palette = $configured[$preference->value] ?? [];

        if (! is_array($palette)) {
            return $configured;
        }

        $configured['light'] = $palette;
        $configured['dark'] = $palette;

        return $configured;
    }
}
