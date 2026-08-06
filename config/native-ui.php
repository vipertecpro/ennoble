<?php

/**
 * Native UI — Theme Tokens
 *
 * Published via `php artisan vendor:publish --tag=native-ui-config`.
 * Edit to customize your app's visual identity in one place.
 *
 * For dynamic per-tenant theming, use Native\Mobile\UI\Theme::merge([...])
 * from a service provider. Runtime merges deep-merge on top of these values.
 *
 * Decision log: /docs/NATIVE-UI-REWRITE-PLAN.md (D — theme layer)
 */

return [

    /*
    |---------------------------------------------------------------------------
    | Theme
    |---------------------------------------------------------------------------
    |
    | Native control colors, 19 Ennoble semantic roles, 4 radii,
    | 4 control font sizes, and one platform font family.
    |
    | "on-X" means "color of content placed ON a surface of color X"
    |   — i.e., text/icons on that background.
    |
    | Color tokens accept:
    |   - CSS hex: '#B91C1C', '#F00', or with alpha '#8B5CF680' (#RRGGBBAA)
    |   - Tailwind palette names: 'red-300', 'orange-800'
    |   - Opacity modifiers on either: 'red-300/20', '#8B5CF6/50'
    |
    | Dark mode is auto-derived from `light` when `dark` is not set. To opt
    | into explicit dark tokens, fill out the `dark` block.
    |
    | The default pairs meet WCAG AA (4.5:1) — if you customize, keep each
    | `on-*` color at 4.5:1 contrast against its background token.
    |
    */

    'theme' => [

        /*
        | Cortex palette — calm zinc foundations, one signature lime.
        | Lime `#C5DB55` = oklch(0.85 0.16 118); it is shared by both themes
        | and always carries dark ink text (`#181C06`). Status hues sit at
        | matched lightness/chroma in oklch — only hue varies.
        | On light, Primary controls are ink; lime stays reserved for the
        | accent CTA of the moment (one per screen).
        */

        'light' => [
            // Native control tokens.
            'primary' => '#1B1B1F',
            'on-primary' => '#FFFFFF',
            'secondary' => '#FFFFFF',
            'on-secondary' => '#1B1B1F',
            'surface' => '#F2F4F9',
            'on-surface' => '#0E1320',
            'background' => '#FFFFFF',
            'on-background' => '#0E1320',
            'surface-variant' => '#E9ECF3',
            'on-surface-variant' => '#4A5163',
            'outline' => '#0C122224',
            'destructive' => '#C53637',
            'on-destructive' => '#FFFFFF',
            'accent' => '#C5DB55',
            'on-accent' => '#181C06',

            // Neon ramp — the vibrant accent family (shared across both themes).
            'accent-cyan' => '#22D3EE',
            'accent-violet' => '#8B5CF6',
            'accent-pink' => '#FF3D8B',
            'accent-amber' => '#FFB020',

            // Ennoble semantic presentation tokens.
            'surface-elevated' => '#FFFFFF',
            'primary-surface' => '#C5DB552E',
            'secondary-surface' => '#EEF1F7',
            'primary-text' => '#0E1320',
            'secondary-text' => '#4A5163',
            'muted-text' => '#8A93A6',
            'divider' => '#0C122214',
            'border' => '#0C12221F',
            'success' => '#1C985A',
            'warning' => '#B07100',
            'danger' => '#C53637',
            'overlay' => '#0F0F1173',
            'pressed' => '#0000000D',
            'selected' => '#C5DB5540',
            'disabled' => '#1B1B1F4D',
            'focus-ring' => '#657D0A80',

            // Badge tier medals (Bronze / Silver / Gold).
            'badge-bronze' => '#B06C38',
            'badge-silver' => '#8A8F99',
            'badge-gold' => '#C79A25',
        ],

        'dark' => [
            // Native control tokens.
            'primary' => '#C5DB55',
            'on-primary' => '#181C06',
            'secondary' => '#161A26',
            'on-secondary' => '#F5F7FA',
            'surface' => '#0F111A',
            'on-surface' => '#F5F7FA',
            'background' => '#000000',
            'on-background' => '#F5F7FA',
            'surface-variant' => '#1E2330',
            'on-surface-variant' => '#A6ADBD',
            'outline' => '#FFFFFF24',
            'destructive' => '#F2716A',
            'on-destructive' => '#1B0A08',
            'accent' => '#C5DB55',
            'on-accent' => '#181C06',

            // Neon ramp — the vibrant accent family (shared across both themes).
            'accent-cyan' => '#22D3EE',
            'accent-violet' => '#8B5CF6',
            'accent-pink' => '#FF3D8B',
            'accent-amber' => '#FFB020',

            // Ennoble semantic presentation tokens.
            'surface-elevated' => '#161A26',
            'primary-surface' => '#C5DB5524',
            'secondary-surface' => '#14171F',
            'primary-text' => '#F5F7FA',
            'secondary-text' => '#A6ADBD',
            'muted-text' => '#6C7486',
            'divider' => '#FFFFFF14',
            'border' => '#FFFFFF1F',
            'success' => '#63D18F',
            'warning' => '#EBA941',
            'danger' => '#F2716A',
            'overlay' => '#00000099',
            'pressed' => '#FFFFFF12',
            'selected' => '#C5DB5533',
            'disabled' => '#F5F5F452',
            'focus-ring' => '#C5DB558C',

            // Badge tier medals (Bronze / Silver / Gold).
            'badge-bronze' => '#D08A5C',
            'badge-silver' => '#C3C8D0',
            'badge-gold' => '#E7C24B',
        ],

        // Corner radii (points / dp) — Cortex: controls M(12) · cards L(16) · sheets XL(24).
        'radius-sm' => 8,
        'radius-md' => 12,
        'radius-lg' => 16,
        'radius-full' => 9999,

        // Font size scale (points / sp) — Cortex: caption 13 · body-small 15 · body/button 17 · title 22.
        'font-sm' => 13,
        'font-md' => 15,
        'font-lg' => 17,
        'font-xl' => 22,

        // App-wide default now comes from the `fonts.default` alias below (Inter).
        // Kept for back-compat; the alias supersedes it.
        'font-family' => 'Inter-Regular',
    ],

    /*
    |---------------------------------------------------------------------------
    | Fonts — semantic aliases
    |---------------------------------------------------------------------------
    |
    | Bundled from resources/fonts/ (via `php artisan native:font`). Reference an
    | alias anywhere a font token works: `font="headline"` in Blade,
    | `->font('headline')` on chrome builders, `$font` on a layout. The `default`
    | alias applies app-wide, superseding the theme `font-family`.
    | Space Grotesk = display / headline / numerics; Inter = body / UI.
    |
    */
    'fonts' => [
        'default' => 'Inter-Regular',
        'medium' => 'Inter-SemiBold',
        'bold' => 'Inter-Bold',
        'headline' => 'SpaceGrotesk-Bold',
        'display' => 'SpaceGrotesk-Bold',
        'numeric' => 'SpaceGrotesk-Medium',
    ],

];
