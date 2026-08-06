{{--
    Rounded-square icon button — the reference "chrome" control (settings, bell,
    back, add). Self-contained leaf: the @press method dispatches to the screen.
    Pass the platform icon enums via :ios / :android.
--}}
@props([
    'ios' => null,
    'android' => null,
    'method' => null,
    'a11yLabel' => '',
    'size' => 20,
    'pressScale' => 0.94,
    'tone' => 'surface',
])

@php
    $bg = $tone === 'ghost'
        ? 'bg-transparent border border-theme-border'
        : 'bg-theme-surface-elevated border border-theme-border';
@endphp

<native:pressable
    class="w-11 h-11 items-center justify-center rounded-2xl {{ $bg }}"
    :press-scale="$pressScale"
    a11y-label="{{ $a11yLabel }}"
    @if ($method) @press="{{ $method }}" @endif
>
    <x-native.ui.icon :ios="$ios" :android="$android" :size="$size" :a11y-label="$a11yLabel" />
</native:pressable>
