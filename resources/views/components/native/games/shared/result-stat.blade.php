@use('App\NativeUI\Tokens\Gradients')

{{--
    End-of-game stat tile — a compact sibling of x-native.ui.stat-badge, sized
    for three-across in the results row rather than Home's roomier grid.

    Everything below was arrived at by measuring the rendered pixels on an iOS
    simulator; three things about EDGE layout make the obvious version wrong:

    1. NO fixed height, NO `justify-center`. On iOS a column with an explicit
       `h-*` ignores `justify-center` — its children end up flush against the
       bottom edge (measured: 53pt of space above the text, 8pt below), which is
       what made the label read as clipped. Nesting a `flex-1` inner column or
       wrapping the content in `<native:spacer>`s did not fix it either. Sizing
       the tile by CONTENT + padding sidesteps vertical distribution entirely.
    2. NEVER `min-h-[…]`. The class parser silently drops arbitrary min-heights
       (no layout key is emitted at all), so a tile written against one is
       really content-sized — the original trap here.
    3. Asymmetric padding is deliberate. A text element's line-box leading sits
       entirely ABOVE its glyph, so equal `py-5` rendered 27pt of space above the
       value and 11pt below it. `pt-3 pb-7` spends the same total height but
       lands the text optically centred (~19pt / ~19pt).

    This tile also depends on its parent NOT being a `justify-center` flex
    column — see the spacers in game-result.blade.php.
--}}
@props([
    'value',
    'label',
    'accent' => 'rose-500',
    'labelColor' => null,
])

@php
    $labelColor = $labelColor ?? $accent;
@endphp

<native:column class="flex-1 pt-3 pb-7 px-2 items-center gap-1 rounded-3xl bg-linear-to-b from-{{ $accent }}/28 via-{{ $accent }}/8 to-transparent border {{ Gradients::hairline() }}">
    <native:text class="text-[24] font-bold leading-tight text-center text-theme-primary-text">
        {{ $value }}
    </native:text>
    <native:text class="text-[10] font-semibold uppercase tracking-wider leading-normal text-center text-{{ $labelColor }}">
        {{ $label }}
    </native:text>
</native:column>
