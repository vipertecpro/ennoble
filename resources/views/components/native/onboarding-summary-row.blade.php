@props([
    'label',
    'value',
])

<native:row class="w-full items-start justify-between gap-4 py-3">
    <native:text class="text-sm text-theme-on-surface-variant">{{ $label }}</native:text>
    <native:text class="flex-1 text-base font-semibold text-right text-theme-on-surface">{{ $value }}</native:text>
</native:row>
