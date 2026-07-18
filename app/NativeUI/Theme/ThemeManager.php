<?php

namespace App\NativeUI\Theme;

use App\Domain\Profile\ProfileService;
use App\Enums\ThemePreference;
use App\NativeUI\Tokens\DesignTokens;
use App\NativeUI\Tokens\MotionToken;
use Native\Mobile\Edge\TailwindParser;
use Native\Mobile\Facades\System;
use Nativephp\NativeUi\Theme;

final class ThemeManager
{
    public function __construct(private readonly ProfileService $profiles) {}

    /**
     * Read the saved preference for the current local profile.
     */
    public function currentPreference(): ThemePreference
    {
        return $this->profiles->current()?->setting?->theme_preference
            ?? ThemePreference::System;
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
     * @return array<string, mixed>
     */
    private function tokensFor(ThemePreference $preference): array
    {
        $configured = config('native-ui.theme', []);

        if (! is_array($configured) || $preference === ThemePreference::System) {
            return is_array($configured) ? $configured : [];
        }

        $palette = $configured[$preference->value] ?? [];

        if (! is_array($palette)) {
            return $configured;
        }

        $configured['light'] = $palette;
        $configured['dark'] = $palette;

        return $configured;
    }
}
