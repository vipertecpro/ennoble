<?php

namespace App\NativeUI\Tokens;

/**
 * Non-color design constants — spacing, motion, icon sizes, touch targets.
 *
 * Colors and typography are NOT here: they live in config/native-ui.php (the
 * theme tokens, consumed via `bg-theme-*` / `text-theme-*` classes and the
 * `theme()` helper) and inline Tailwind classes, per the NativePHP theming
 * model. This class only holds the numeric constants the framework's theme
 * config does not cover.
 */
final class DesignTokens
{
    /**
     * The semantic color-token keys every theme block must define — used to
     * validate config/native-ui.php stays complete.
     *
     * @var list<string>
     */
    public const SEMANTIC_COLORS = [
        'background',
        'surface',
        'surface-elevated',
        'primary-surface',
        'secondary-surface',
        'primary-text',
        'secondary-text',
        'muted-text',
        'divider',
        'border',
        'accent',
        'accent-cyan',
        'accent-violet',
        'accent-pink',
        'accent-amber',
        'success',
        'warning',
        'danger',
        'overlay',
        'pressed',
        'selected',
        'disabled',
        'focus-ring',
    ];

    /**
     * 4pt base grid — XS 4 · SM 8 · MD 12 · LG 16 · XL 24 · 2XL 32 · 3XL 48.
     *
     * @var array<string, int>
     */
    public const SPACING = [
        'xs' => 4,
        'sm' => 8,
        'md' => 12,
        'lg' => 16,
        'xl' => 24,
        '2xl' => 32,
        '3xl' => 48,
    ];

    /**
     * Motion durations (ms) — collapse to 0 under Reduced Motion via ThemeManager.
     *
     * @var array<string, int>
     */
    public const MOTION_DURATION = [
        'fast' => 150,
        'normal' => 250,
        'slow' => 250,
        'spring' => 400,
        'success' => 400,
        'error' => 300,
    ];

    /**
     * @var array<string, float>
     */
    public const OPACITY = [
        'disabled' => 0.38,
        'muted' => 0.68,
        'overlay' => 0.6,
        'pressed' => 0.9,
    ];

    /**
     * @var array<string, int>
     */
    public const ICON_SIZE = [
        'small' => 18,
        'medium' => 24,
        'large' => 32,
        'hero' => 48,
    ];

    public const SCREEN_PADDING = 16;

    public const COMPONENT_SPACING = 16;

    public const MINIMUM_TOUCH_TARGET = 44;

    /**
     * Resolve a reusable motion duration.
     */
    public static function motionDuration(MotionToken $token): int
    {
        return self::MOTION_DURATION[$token->value];
    }
}
