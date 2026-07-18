<?php

/**
 * Native UI — Theme Tokens
 *
 * Published via `php artisan vendor:publish --tag=native-ui-config`.
 * Edit to customize your app's visual identity in one place.
 *
 * For dynamic per-tenant theming, use Nativephp\NativeUi\Theme::merge([...])
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

        'light' => [
            // Native control tokens.
            'primary' => '#1F6F63',
            'on-primary' => '#FFFFFF',
            'secondary' => '#E8EBE8',
            'on-secondary' => '#242628',
            'surface' => '#FCFCFB',
            'on-surface' => '#18191B',
            'background' => '#F5F5F2',
            'on-background' => '#18191B',
            'surface-variant' => '#ECEDEA',
            'on-surface-variant' => '#62666A',
            'outline' => '#D9DBD7',
            'destructive' => '#A63D45',
            'on-destructive' => '#FFFFFF',
            'accent' => '#1F6F63',
            'on-accent' => '#FFFFFF',

            // Ennoble semantic presentation tokens.
            'surface-elevated' => '#FFFFFF',
            'primary-surface' => '#E7F0ED',
            'secondary-surface' => '#EFF0ED',
            'primary-text' => '#18191B',
            'secondary-text' => '#3F4347',
            'muted-text' => '#73777C',
            'divider' => '#E5E6E2',
            'border' => '#D9DBD7',
            'success' => '#347A52',
            'warning' => '#9A6A1D',
            'danger' => '#A63D45',
            'overlay' => '#0A0B0CB8',
            'pressed' => '#18191B0F',
            'selected' => '#D9E9E4',
            'disabled' => '#E4E5E2',
            'focus-ring' => '#398F80',
        ],

        'dark' => [
            // Native control tokens.
            'primary' => '#70CDBB',
            'on-primary' => '#0D0F10',
            'secondary' => '#292B2F',
            'on-secondary' => '#F3F4F4',
            'surface' => '#15171A',
            'on-surface' => '#F3F4F4',
            'background' => '#0D0F11',
            'on-background' => '#F3F4F4',
            'surface-variant' => '#222428',
            'on-surface-variant' => '#A9ADB2',
            'outline' => '#32353B',
            'destructive' => '#EF8B91',
            'on-destructive' => '#1A0D0F',
            'accent' => '#70CDBB',
            'on-accent' => '#0D0F10',

            // Ennoble semantic presentation tokens.
            'surface-elevated' => '#1B1D21',
            'primary-surface' => '#202327',
            'secondary-surface' => '#191B1E',
            'primary-text' => '#F3F4F4',
            'secondary-text' => '#C5C8CC',
            'muted-text' => '#8D9299',
            'divider' => '#25282D',
            'border' => '#32353B',
            'success' => '#78C596',
            'warning' => '#D8B36E',
            'danger' => '#EF8B91',
            'overlay' => '#000000C2',
            'pressed' => '#FFFFFF12',
            'selected' => '#27332F',
            'disabled' => '#24262B',
            'focus-ring' => '#91DCCA',
        ],

        // Corner radii (points / dp).
        'radius-sm' => 10,
        'radius-md' => 16,
        'radius-lg' => 24,
        'radius-full' => 9999,

        // Font size scale (points / sp).
        'font-sm' => 14,
        'font-md' => 16,
        'font-lg' => 20,
        'font-xl' => 24,

        // 'System' resolves to the platform default (San Francisco on iOS, Roboto on Android).
        // Set a bundled font token to apply it app-wide — a file from your app's
        // resources/fonts/ minus the extension (e.g. 'Inter-Regular'). Download one
        // with `php artisan native:font Inter`. Per-element `font` attributes and
        // font-serif / font-mono classes still win over this default.
        'font-family' => 'System',
    ],

];
