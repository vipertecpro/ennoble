@props([
    'label',
    'earned',
    'total',
    'color',
])

<native:row class="items-center gap-2">
    <native:column class="w-3 h-3 rounded-full bg-theme-{{ $color }}" />
    <native:text class="text-[12] font-semibold text-theme-secondary-text">{{ $label }} {{ $earned }}/{{ $total }}</native:text>
</native:row>
