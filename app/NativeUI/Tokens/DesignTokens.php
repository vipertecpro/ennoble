<?php

namespace App\NativeUI\Tokens;

final class DesignTokens
{
    /**
     * @var array<string, string>
     */
    public const TYPOGRAPHY = [
        'display' => 'text-4xl font-bold leading-tight',
        'heading' => 'text-2xl font-semibold leading-tight',
        'body' => 'text-base font-normal leading-relaxed',
        'label' => 'text-sm font-semibold leading-normal',
        'numeric' => 'text-3xl font-bold leading-none',
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
    ];

    /**
     * @var array<string, int>
     */
    public const CORNER_RADII = [
        'small' => 8,
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
        'medium' => 6,
        'high' => 12,
    ];

    /**
     * @var array<string, int>
     */
    public const MOTION_DURATION = [
        'fast' => 120,
        'normal' => 220,
        'slow' => 360,
        'spring' => 420,
        'success' => 480,
        'error' => 280,
    ];

    /**
     * @var array<string, float>
     */
    public const OPACITY = [
        'disabled' => 0.38,
        'muted' => 0.62,
        'overlay' => 0.72,
        'pressed' => 0.86,
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

    public const SCREEN_PADDING = 20;

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
