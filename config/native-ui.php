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
    | 17 color tokens, 4 radii, 4 font sizes, font family.
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
            // Primary brand color — used for filled buttons, active states, key accents.
            'primary' => '#5B43D6',
            'on-primary' => '#FFFFFF',

            // Secondary / muted action color.
            'secondary' => '#405A73',
            'on-secondary' => '#FFFFFF',

            // Surface = cards, sheets, dialogs. Background = page root.
            'surface' => '#FFFFFF',
            'on-surface' => '#1E2430',
            'background' => '#F7F6FB',
            'on-background' => '#1E2430',

            // Surface variant = filled text fields, muted tonal surfaces.
            // on-surface-variant = muted label/hint text on those surfaces.
            'surface-variant' => '#ECEAF5',
            'on-surface-variant' => '#5D6472',

            // Outline = neutral borders (text fields, dividers, cards).
            'outline' => '#D7D3E3',

            // Destructive actions — maps to `variant="destructive"` on components.
            'destructive' => '#B42332',
            'on-destructive' => '#FFFFFF',

            // Tertiary accent — for highlights, badges, emphasis not covered by primary.
            'accent' => '#B65B24',
            'on-accent' => '#FFFFFF',
        ],

        'dark' => [
            // Leave empty or partial to auto-derive from `light` (luminance inversion).
            // Specify any token here to override the derived value.
            'primary' => '#A99AF5',
            'on-primary' => '#17161D',

            'secondary' => '#9FB5CC',
            'on-secondary' => '#151820',

            'surface' => '#24232C',
            'on-surface' => '#F6F4FA',
            'background' => '#17161D',
            'on-background' => '#F6F4FA',

            'surface-variant' => '#32303C',
            'on-surface-variant' => '#B8B4C4',

            'outline' => '#4A4658',

            'destructive' => '#FF8995',
            'on-destructive' => '#17161D',

            'accent' => '#F0A46E',
            'on-accent' => '#17161D',
        ],

        // Corner radii (points / dp).
        'radius-sm' => 8,
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
