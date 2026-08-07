@use('App\NativeUI\Tokens\ConsolePalette')

{{--
    One HUD readout: a cyan-outlined cell with a mono label above a neon value.

    The label and the value are two text nodes in a column — never a row, which
    collapses in this rail. The value keys by ROLE so its digits roll rather
    than the whole readout being rebuilt.
--}}
@props([
    'label',
    'value',
    'key',
    'accent' => null,
    'motionDuration' => 0,
])

@php
    $accent = $accent ?? ConsolePalette::VALUE;
@endphp

<native:column class="w-20 items-center gap-0.5 rounded-lg px-2 py-2 border border-[{{ ConsolePalette::LINE }}]/35 bg-[{{ ConsolePalette::LINE }}]/5">
    <native:text
        font="mono"
        class="text-[8] uppercase tracking-widest text-[{{ ConsolePalette::LABEL }}]"
    >{{ $label }}</native:text>

    <native:text
        native:key="{{ $key }}"
        font="mono-bold"
        class="text-[16] text-[{{ $accent }}]"
        content-transition="numeric"
        :animate-duration="$motionDuration"
        animate-easing="spring"
    >{{ $value }}</native:text>
</native:column>
