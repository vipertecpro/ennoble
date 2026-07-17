@props([
    'ios',
    'android',
    'label',
    'value',
])

<native:column class="flex-1 gap-2 rounded-2xl bg-theme-surface-variant p-3">
    <native:row class="items-center gap-2">
        <x-native.icon :ios="$ios" :android="$android" :size="18" />
        <native:text class="text-xs font-semibold text-theme-on-surface-variant">{{ $label }}</native:text>
    </native:row>
    <native:text class="text-base font-semibold leading-tight text-theme-on-surface">{{ $value }}</native:text>
</native:column>
