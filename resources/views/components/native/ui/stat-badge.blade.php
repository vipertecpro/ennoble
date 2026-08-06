{{--
    Vibrant gradient stat badge — extracted from the Home "At a glance" row.

    Icon / lottie slot on top, a big number, then a colored uppercase label,
    all sitting on a two-tone gradient fill with a glowing coloured border.

    Colour is driven by the `accent` prop (the identity hue used for the
    gradient's first stop and the border). `accentTo` sets the second gradient
    stop (defaults to `accent`), and `labelColor` sets the uppercase label
    (defaults to `accent`). Pass palette names, e.g.:
        accent="orange-500" accentTo="red-600" labelColor="orange-400"
        accent="lime-400"   accentTo="cyan-500" labelColor="lime-400"

    The number and label live directly in the <native:column> to avoid the iOS
    nested-centred-row collapse gotcha.
--}}

@props([
    'value',
    'label',
    'accent' => 'lime-400',
    'accentTo' => null,
    'labelColor' => null,
])

@php
    $accentTo = $accentTo ?? $accent;
    $labelColor = $labelColor ?? $accent;
@endphp

<native:column class="flex-1 items-center gap-1 rounded-3xl bg-linear-to-b from-{{ $accent }}/30 to-{{ $accentTo }}/10 border border-{{ $accent }}/40 shadow-lg py-5 px-3">
    @if (isset($icon))
        <native:column class="w-14 h-14">
            {{ $icon }}
        </native:column>
    @endif
    <native:text class="text-[32] font-bold leading-tight text-theme-primary-text">{{ $value }}</native:text>
    <native:text class="text-[11] font-semibold uppercase tracking-widest text-{{ $labelColor }}">{{ $label }}</native:text>
</native:column>
