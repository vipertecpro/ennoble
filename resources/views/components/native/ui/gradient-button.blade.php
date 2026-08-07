{{--
    Glossy gradient primary button — the "game-premium" CTA.

    A bright rose -> orange gradient pressable with a bold, dark, centred label,
    a full-round shape, depth via shadow, and press-scale feedback. The @press
    handler is passed through by method name.

    `variant="ghost"` gives the quiet companion: no fill, a hairline border, and
    accent-coloured ink — for the secondary action beside a solid CTA.

    Pass `iosIcon` / `androidIcon` (typed icon enum cases) to put a leading glyph
    before the label. `width` defaults to `w-full`; pass `flex-1` to sit two of
    these side by side in a row.

    NOTE: the label sits directly in the <native:column> (not in a nested
    justify-center row) to dodge the iOS flex gotcha where a nested centred row
    collapses to zero width and hides its content. The icon variant nests a row,
    but a content-sized one with no justify-center — centring stays the parent
    column's job.
--}}

@use('App\NativeUI\Tokens\Gradients')
@use('Native\Mobile\UI\Theme')

@props([
    'label',
    'press' => null,
    'pressScale' => 0.96,
    'variant' => 'solid',
    'width' => 'w-full',
    'iosIcon' => null,
    'androidIcon' => null,
    'class' => '',
])

@php
    $ghost = $variant === 'ghost';
    $tokens = Theme::all();

    $surface = $ghost
        ? 'border '.Gradients::hairline()
        : 'bg-linear-to-r from-rose-500 to-orange-400 shadow-lg';
    $ink = $ghost ? 'text-theme-accent' : 'text-black';

    // Icon color has to be handed over as a value (icons don't take ink
    // classes), so mirror whatever the label ink resolves to.
    $iconColor = $ghost
        ? (data_get($tokens, 'light.accent') ?? config('native-ui.theme.light.accent', '#F43F5E'))
        : '#000000';
    $iconDarkColor = $ghost
        ? (data_get($tokens, 'dark.accent') ?? config('native-ui.theme.dark.accent', '#F43F5E'))
        : '#000000';
@endphp

<native:pressable
    class="{{ $width }} rounded-full py-4 px-6 {{ $surface }} {{ $class }}"
    :press-scale="$pressScale"
    a11y-label="{{ $label }}"
    @if ($press) @press="{{ $press }}" @endif
>
    <native:column class="w-full items-center">
        @if ($iosIcon !== null && $androidIcon !== null)
            <native:row class="items-center gap-2">
                <x-native.ui.icon
                    :ios="$iosIcon"
                    :android="$androidIcon"
                    :size="18"
                    :color="$iconColor"
                    :dark-color="$iconDarkColor"
                />
                <native:text class="text-[17] font-bold tracking-tight {{ $ink }}">{{ $label }}</native:text>
            </native:row>
        @else
            <native:text class="text-[17] font-bold tracking-tight {{ $ink }}">{{ $label }}</native:text>
        @endif
    </native:column>
</native:pressable>
