@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

{{--
    One Stack control: a dark rounded key, as on a real handheld.

    A pressable rather than <native:button> because the buttons here are
    icon-only squares of a fixed size, and button's variant styling is
    theme-driven by design — it will not take a per-instance shape.
--}}
@props([
    'action',
    'label',
    'press',
    'primary' => false,
])

@php
    [$ios, $android] = match ($action) {
        'rotate' => [Ios::ArrowClockwise, AndroidOutlined::RotateRight],
        'left' => [Ios::ChevronLeft, AndroidOutlined::ChevronLeft],
        'right' => [Ios::ChevronRight, AndroidOutlined::ChevronRight],
        'down' => [Ios::ChevronDown, AndroidOutlined::ExpandMore],
        default => [Ios::ArrowDownToLine, AndroidOutlined::VerticalAlignBottom],
    };
@endphp

<native:pressable
    @press="{{ $press }}"
    :press-scale="0.92"
    a11y-label="{{ $label }}"
    class="w-14 h-12 items-center justify-center rounded-2xl {{ $primary ? 'bg-theme-accent' : 'bg-theme-surface-variant' }}"
>
    <x-native.ui.icon :ios="$ios" :android="$android" :size="20" />
</native:pressable>
