<?php

namespace App\NativeUI\Tokens;

/**
 * Cortex design tokens — the single source of truth for Ennoble's visual
 * language. Calm zinc foundations, one signature lime accent, typography
 * as hierarchy, 4pt spacing grid, elevation over borders.
 *
 * Color values live in config/native-ui.php (semantic tokens, both themes).
 * Everything else — type scale, spacing, radii, elevation, motion — lives
 * here. Screens consume tokens; they never invent values.
 */
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
     * Cortex type scale — SF Pro (system), display sizes with tight
     * tracking. Sizes in points: 44/34/28/22/17/15/13/12.
     *
     * @var array<string, string>
     */
    public const TYPOGRAPHY = [
        'display-xl' => 'text-[44] font-bold tracking-tight leading-tight',
        'display-large' => 'text-[34] font-bold tracking-tight leading-tight',
        'headline' => 'text-[28] font-bold tracking-tight leading-tight',
        'title' => 'text-[22] font-semibold tracking-tight leading-tight',
        'section' => 'text-[17] font-semibold leading-tight',
        'body' => 'text-[17] font-normal leading-normal',
        'body-small' => 'text-[15] font-normal leading-normal',
        'caption' => 'text-[13] font-normal leading-normal',
        'label' => 'text-[12] font-semibold tracking-widest uppercase',
        'button' => 'text-[17] font-semibold leading-normal',
        'badge' => 'text-[12] font-semibold leading-normal',
        'numeric' => 'text-[28] font-bold tracking-tight leading-tight',
    ];

    /**
     * 4pt base grid — XS 4 · SM 8 · MD 12 · LG 16 · XL 24 · 2XL 32 · 3XL 48.
     * No arbitrary values.
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
     * Screen gutter LG (16) · card padding LG–XL · section gaps 2XL.
     *
     * @var array<string, int>
     */
    public const LAYOUT_SPACING = [
        'screen-margin' => 16,
        'section' => 32,
        'card' => 20,
        'content' => 16,
        'compact' => 12,
        'touch' => 12,
    ];

    /**
     * Cortex radii — S 8 · M 12 (controls) · L 16 (cards) · XL 24 (sheets) · Full.
     *
     * @var array<string, int>
     */
    public const CORNER_RADII = [
        'small' => 8,
        'medium' => 12,
        'large' => 16,
        'xl' => 24,
        'full' => 9999,
    ];

    /**
     * Elevation over borders — Flat (inline) · Raised (cards at rest) ·
     * Floating (active card) · Overlay (dialogs).
     *
     * @var array<string, int>
     */
    public const ELEVATION = [
        'none' => 0,
        'low' => 1,
        'medium' => 8,
        'high' => 16,
    ];

    /**
     * Motion communicates, never decorates — quick 150 (presses, toggles) ·
     * standard 250 (navigation, reveals) · celebrate 400 (success only;
     * overshoot allowed only here) · error 300 (shake, no red flood).
     * All collapse to 0 under Reduced Motion via ThemeManager.
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
     * Card family — radius L (16), Raised elevation, no borders (elevation
     * over borders; hairlines only inside lists). Coming-soon placeholders
     * keep an outline so absence reads as absence.
     *
     * @var array<string, string>
     */
    public const CARD_VARIANTS = [
        'hero' => 'rounded-2xl bg-theme-surface-elevated shadow-sm',
        'game' => 'rounded-2xl bg-theme-surface shadow-sm',
        'metric' => 'flex-1 rounded-2xl bg-theme-surface shadow-sm',
        'achievement' => 'rounded-2xl bg-theme-surface shadow-sm',
        'coming-soon' => 'rounded-2xl border border-theme-border bg-theme-secondary-surface',
        'standard' => 'rounded-2xl bg-theme-surface shadow-sm',
    ];

    /**
     * @var array<string, string>
     */
    public const CARD_CONTENT_VARIANTS = [
        'hero' => 'gap-5',
        'game' => 'gap-4',
        'metric' => 'gap-2',
        'achievement' => 'gap-4',
        'coming-soon' => 'gap-4',
        'standard' => 'gap-4',
    ];

    /**
     * Card insets as Tailwind spacing steps (×4pt): hero 20, rest 16.
     *
     * @var array<string, string>
     */
    public const CARD_INSET_VARIANTS = [
        'hero' => '5',
        'game' => '4',
        'metric' => '4',
        'achievement' => '4',
        'coming-soon' => '4',
        'standard' => '4',
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
     * Buttons — one family: 50pt height, radius Full, Button type style.
     */
    public const BUTTON_HEIGHT = 50;

    /**
     * Resolve a reusable motion duration.
     */
    public static function motionDuration(MotionToken $token): int
    {
        return self::MOTION_DURATION[$token->value];
    }
}
