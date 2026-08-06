@props([
    'ios',
    'android',
    'label',
    'value',
    'accent' => 'lime-400',
    'accentTo' => null,
    'labelColor' => null,
])

@php
    $accentTo = $accentTo ?? 'cyan-500';
    $labelColor = $labelColor ?? $accent;
@endphp

<native:column class="flex-1 gap-2 rounded-2xl bg-linear-to-b from-{{ $accent }}/25 to-{{ $accentTo }}/10 border border-{{ $accent }}/40 shadow-lg p-3">
    <native:row class="items-center gap-2">
        <x-native.ui.icon :ios="$ios" :android="$android" :size="18" />
        <native:text class="text-[11] font-semibold uppercase tracking-wider text-{{ $labelColor }}">{{ $label }}</native:text>
    </native:row>
    <native:text class="text-[17] font-bold leading-tight text-theme-primary-text">{{ $value }}</native:text>
</native:column>
