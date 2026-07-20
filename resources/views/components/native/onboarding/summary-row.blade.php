@props([
    'label',
    'value',
])

<native:row class="items-start justify-between gap-4 py-3">
    <native:text class="text-[13] text-theme-secondary-text">{{ $label }}</native:text>
    <native:text class="flex-1 text-[15] font-semibold text-right text-theme-primary-text">{{ $value }}</native:text>
</native:row>
