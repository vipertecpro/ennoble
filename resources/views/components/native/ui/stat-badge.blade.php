{{--
    Vibrant gradient stat badge — extracted from the Home "At a glance" row.

    Icon / lottie slot on top, a big number, then a colored uppercase label,
    all sitting on a two-tone gradient fill with a glowing coloured border.

    Colour is driven by the `accent` prop (the identity hue used for the
    gradient's first stop and the border). `accentTo` sets the second gradient
    stop (defaults to `accent`), and `labelColor` sets the uppercase label
    (defaults to `accent`). Pass palette names, e.g.:
        accent="orange-500" accentTo="red-600" labelColor="orange-400"
        accent="rose-500"   accentTo="cyan-500" labelColor="rose-500"

    The number and label live directly in the <native:column> to avoid the iOS
    nested-centred-row collapse gotcha.
--}}

@use('App\NativeUI\Tokens\Gradients')

@props([
    'value',
    'label',
    'accent' => 'rose-500',
    'accentTo' => null,
    'labelColor' => null,
    'valueSize' => 'text-[34]',
])

@php
    $accentTo = $accentTo ?? $accent;
    $labelColor = $labelColor ?? $accent;
@endphp

<native:column class="flex-1 min-h-[112px] items-center justify-center gap-1 rounded-3xl bg-linear-to-b from-{{ $accent }}/28 via-{{ $accent }}/8 to-transparent border {{ Gradients::hairline() }} py-5 px-3">
    @if (isset($icon))
        <native:column class="w-12 h-12 items-center justify-center">
            {{ $icon }}
        </native:column>
    @endif
    <native:text class="{{ $valueSize }} font-bold leading-tight text-center text-theme-primary-text">{{ $value }}</native:text>
    <native:text class="text-[11] font-semibold uppercase tracking-widest text-{{ $labelColor }}">{{ $label }}</native:text>
</native:column>
