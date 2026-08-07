@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\ConsolePalette')

{{--
    One control on the console: a circular key inside a ring.

    Two nested circles rather than one, because the ring is what makes a button
    read as instrument rather than app chrome — the reference draws a dial
    around every control. Nested COLUMNS, never a row: a row here collapses.
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

    $ring = $primary ? ConsolePalette::VALUE : ConsolePalette::LINE;
@endphp

<native:pressable
    @press="{{ $press }}"
    :press-scale="0.9"
    a11y-label="{{ $label }}"
    class="w-16 h-16 items-center justify-center rounded-full border-2 border-[{{ $ring }}]/40"
>
    <native:column class="w-12 h-12 items-center justify-center rounded-full border border-[{{ $ring }}]/70 bg-[{{ $ring }}]/10">
        <x-native.ui.icon :ios="$ios" :android="$android" :size="22" :color="$ring" :dark-color="$ring" />
    </native:column>
</native:pressable>
