@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\ConsolePalette')

{{--
    One control on the console: a large circular key inside a dial.

    THREE nested circles, not one. The reference draws a ring, a gap, then a
    second ring around every control — that layering is what makes a button
    read as an instrument rather than as app chrome, and it is the single
    biggest difference between "app with round buttons" and "console".

    Nested COLUMNS all the way down. A row at any level here collapses.
--}}
@props([
    'action',
    'label',
    'press',
    'primary' => false,
    'size' => 64,
    // Placed by transform, because the cluster is a stack: see the screen.
    'translateX' => 0,
    'translateY' => 0,
])

@php
    [$ios, $android] = match ($action) {
        'rotate' => [Ios::ArrowClockwise, AndroidOutlined::RotateRight],
        'left' => [Ios::ChevronLeft, AndroidOutlined::ChevronLeft],
        'right' => [Ios::ChevronRight, AndroidOutlined::ChevronRight],
        'down' => [Ios::ChevronDown, AndroidOutlined::ExpandMore],
        default => [Ios::ArrowDownToLine, AndroidOutlined::VerticalAlignBottom],
    };

    $ring = $primary ? ConsolePalette::value() : ConsolePalette::line();
    $inner = (int) round($size * 0.74);
    $core = (int) round($size * 0.54);
    $glyph = (int) round($size * 0.3);
@endphp

<native:pressable
    @press="{{ $press }}"
    :press-scale="0.9"
    :translate-x="$translateX"
    :translate-y="$translateY"
    a11y-label="{{ $label }}"
    class="w-[{{ $size }}px] h-[{{ $size }}px] items-center justify-center rounded-full border-[1.5px] border-[{{ $ring }}]/25"
>
    <native:column class="w-[{{ $inner }}px] h-[{{ $inner }}px] items-center justify-center rounded-full border-[1.5px] border-[{{ $ring }}]/55">
        <native:column class="w-[{{ $core }}px] h-[{{ $core }}px] items-center justify-center rounded-full border border-[{{ $ring }}]/80 bg-[{{ $ring }}]/12">
            <x-native.ui.icon :ios="$ios" :android="$android" :size="$glyph" :color="$ring" :dark-color="$ring" />
        </native:column>
    </native:column>
</native:pressable>
