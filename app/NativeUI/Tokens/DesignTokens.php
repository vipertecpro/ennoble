<?php

namespace App\NativeUI\Tokens;

final class DesignTokens
{
    /**
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
     * @var array<string, string>
     */
    public const TYPOGRAPHY = [
        'display-xl' => 'text-5xl font-bold leading-tight',
        'display-large' => 'text-4xl font-bold leading-tight',
        'headline' => 'text-3xl font-bold leading-tight',
        'title' => 'text-2xl font-semibold leading-tight',
        'section' => 'text-xl font-semibold leading-tight',
        'body' => 'text-base font-normal leading-relaxed',
        'body-small' => 'text-sm font-normal leading-relaxed',
        'caption' => 'text-xs font-semibold leading-normal',
        'button' => 'text-base font-semibold leading-normal',
        'badge' => 'text-xs font-semibold leading-normal',
        'numeric' => 'text-3xl font-bold leading-tight',
    ];

    /**
     * @var array<string, int>
     */
    public const SPACING = [
        'xs' => 4,
        'sm' => 8,
        'md' => 12,
        'lg' => 16,
        'xl' => 20,
        '2xl' => 24,
        '3xl' => 32,
        '4xl' => 40,
        '5xl' => 48,
    ];

    /**
     * @var array<string, int>
     */
    public const LAYOUT_SPACING = [
        'screen-margin' => 20,
        'section' => 24,
        'card' => 20,
        'content' => 16,
        'compact' => 12,
        'touch' => 12,
    ];

    /**
     * @var array<string, int>
     */
    public const CORNER_RADII = [
        'small' => 10,
        'medium' => 16,
        'large' => 24,
        'full' => 9999,
    ];

    /**
     * @var array<string, int>
     */
    public const ELEVATION = [
        'none' => 0,
        'low' => 1,
        'medium' => 3,
        'high' => 8,
    ];

    /**
     * @var array<string, int>
     */
    public const MOTION_DURATION = [
        'fast' => 110,
        'normal' => 180,
        'slow' => 260,
        'spring' => 300,
        'success' => 340,
        'error' => 180,
    ];

    /**
     * @var array<string, float>
     */
    public const OPACITY = [
        'disabled' => 0.44,
        'muted' => 0.68,
        'overlay' => 0.76,
        'pressed' => 0.9,
    ];

    /**
     * @var array<string, string>
     */
    public const CARD_VARIANTS = [
        'hero' => 'rounded-3xl bg-theme-primary-surface',
        'workout' => 'rounded-3xl border border-theme-border bg-theme-surface-elevated',
        'game' => 'rounded-3xl border border-theme-border bg-theme-surface-elevated',
        'metric' => 'flex-1 rounded-2xl bg-theme-secondary-surface',
        'achievement' => 'rounded-3xl border border-theme-border bg-theme-surface-elevated',
        'coming-soon' => 'rounded-3xl border border-theme-border bg-theme-secondary-surface',
        'standard' => 'rounded-3xl border border-theme-border bg-theme-surface-elevated',
    ];

    /**
     * @var array<string, string>
     */
    public const CARD_CONTENT_VARIANTS = [
        'hero' => 'gap-6',
        'workout' => 'gap-5',
        'game' => 'gap-5',
        'metric' => 'gap-2',
        'achievement' => 'gap-4',
        'coming-soon' => 'gap-4',
        'standard' => 'gap-4',
    ];

    /**
     * @var array<string, string>
     */
    public const CARD_INSET_VARIANTS = [
        'hero' => '6',
        'workout' => '5',
        'game' => '5',
        'metric' => '4',
        'achievement' => '5',
        'coming-soon' => '5',
        'standard' => '5',
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

    public const SCREEN_PADDING = self::LAYOUT_SPACING['screen-margin'];

    public const COMPONENT_SPACING = self::LAYOUT_SPACING['content'];

    public const MINIMUM_TOUCH_TARGET = 44;

    /**
     * Resolve a reusable motion duration.
     */
    public static function motionDuration(MotionToken $token): int
    {
        return self::MOTION_DURATION[$token->value];
    }
}
