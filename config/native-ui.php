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
        | Palette — cool slate foundations with a warm coral/rose accent.
        | Dark is a deep slate/navy (slate-900 ground, slate-800 surfaces);
        | light is its clean counterpart (white ground, slate-100 surfaces,
        | slate inks). The signature accent is rose, with a rose→orange CTA
        | gradient. All values are Tailwind palette classes (names, named
        | colors, and /opacity modifiers) — no raw hex.
        */

        'light' => [
            // Native control tokens.
            'primary' => 'rose-600',
            'on-primary' => 'white',
            'secondary' => 'white',
            'on-secondary' => 'slate-900',
            'surface' => 'slate-100',
            'on-surface' => 'slate-900',
            'background' => 'white',
            'canvas' => 'white',
            'on-background' => 'slate-900',
            'surface-variant' => 'slate-200',
            'on-surface-variant' => 'slate-600',
            'outline' => 'slate-900/15',
            'destructive' => 'red-600',
            'on-destructive' => 'white',
            'accent' => 'rose-600',
            'on-accent' => 'white',

            // Neon ramp — deepened for LIGHT so accent text / icons stay legible on
            // white (dark mode keeps the bright neon values).
            'accent-cyan' => 'cyan-600',
            'accent-violet' => 'violet-700',
            'accent-pink' => 'pink-600',
            'accent-amber' => 'amber-700',

            // Ennoble semantic presentation tokens.
            'surface-elevated' => 'slate-100',
            'primary-surface' => 'rose-500/15',
            'secondary-surface' => 'slate-100',
            'primary-text' => 'slate-900',
            'secondary-text' => 'slate-600',
            'muted-text' => 'slate-400',
            'divider' => 'slate-900/6',
            'border' => 'slate-900/20',
            'success' => 'emerald-600',
            'warning' => 'amber-700',
            'danger' => 'red-600',
            'overlay' => 'slate-950/50',
            'pressed' => 'black/5',
            'selected' => 'rose-500/25',
            'disabled' => 'slate-900/30',
            'focus-ring' => 'rose-600/50',

            // Badge tier medals (Bronze / Silver / Gold).
            'badge-bronze' => 'amber-700',
            'badge-silver' => 'slate-400',
            'badge-gold' => 'yellow-600',
        ],

        'dark' => [
            // Native control tokens.
            'primary' => 'rose-500',
            'on-primary' => 'white',
            'secondary' => 'slate-800',
            'on-secondary' => 'slate-100',
            'surface' => 'slate-800',
            'on-surface' => 'slate-100',
            'background' => 'slate-900',
            'canvas' => 'slate-900',
            'on-background' => 'slate-100',
            'surface-variant' => 'slate-700',
            'on-surface-variant' => 'slate-400',
            'outline' => 'white/15',
            'destructive' => 'red-400',
            'on-destructive' => 'red-950',
            'accent' => 'rose-500',
            'on-accent' => 'white',

            // Neon ramp — the vibrant accent family (shared across both themes).
            'accent-cyan' => 'cyan-400',
            'accent-violet' => 'violet-500',
            'accent-pink' => 'pink-500',
            'accent-amber' => 'amber-400',

            // Ennoble semantic presentation tokens.
            'surface-elevated' => 'slate-800',
            'primary-surface' => 'rose-500/15',
            'secondary-surface' => 'slate-800',
            'primary-text' => 'slate-100',
            'secondary-text' => 'slate-400',
            'muted-text' => 'slate-500',
            'divider' => 'white/6',
            'border' => 'white/15',
            'success' => 'emerald-400',
            'warning' => 'amber-400',
            'danger' => 'red-400',
            'overlay' => 'black/60',
            'pressed' => 'white/10',
            'selected' => 'rose-500/20',
            'disabled' => 'slate-100/30',
            'focus-ring' => 'rose-400/55',

            // Badge tier medals (Bronze / Silver / Gold).
            'badge-bronze' => 'orange-400',
            'badge-silver' => 'slate-300',
            'badge-gold' => 'amber-300',
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

        // The quote ticker's own voice — a serif, deliberately unlike the
        // Inter/Space Grotesk pairing the rest of the app is set in, so the
        // strip reads as a quotation rather than as more UI copy.
        'quote' => 'Lora-Italic',
        'quote-source' => 'Lora-Regular',

        // The Home masthead's terminal voice.
        'mono' => 'JetBrainsMono-Regular',
        'mono-bold' => 'JetBrainsMono-Bold',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gradients
    |--------------------------------------------------------------------------
    |
    | The single source of truth for every gradient / accent hue in the app —
    | read by App\NativeUI\Tokens\Gradients. EDGE gradient STOPS must be Tailwind
    | palette names (from-lime-400): arbitrary #hex stops do not parse, and
    | theme-token stops bake the light value into both modes, so hues are authored
    | here as palette names rather than in the theme (hex) block above.
    |
    | - screen / hairline: a full class string per appearance (light | dark).
    | - cta: the primary action fill (one class string, appearance-independent).
    | - games / onboarding_tones: [from, to] hue pairs, expanded into glass /
    |   border / solid recipes in code.
    |
    */
    'gradients' => [
        'screen' => [
            'light' => 'bg-linear-to-b from-slate-200 via-slate-100 to-slate-200',
            'dark' => 'bg-linear-to-b from-slate-800 via-slate-900 to-slate-900',
        ],

        'hairline' => [
            'light' => 'border-black/10',
            'dark' => 'border-white/12',
        ],

        'cta' => 'bg-linear-to-r from-rose-500 to-orange-400',

        'fallback' => ['blue-400', 'cyan-500'],

        'games' => [
            'word-match' => ['blue-400', 'emerald-500'],
            'quick-math' => ['cyan-400', 'sky-500'],
            'recall' => ['violet-500', 'fuchsia-500'],
            'flow' => ['indigo-500', 'cyan-400'],
            'signal' => ['amber-400', 'orange-500'],
        ],

        'onboarding_tones' => [
            'lime' => ['rose-500', 'orange-400'],
            'cyan' => ['cyan-400', 'sky-500'],
            'violet' => ['violet-500', 'fuchsia-500'],
            'amber' => ['amber-400', 'orange-500'],
            'pink' => ['pink-400', 'rose-500'],
        ],
    ],

];
