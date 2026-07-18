@props([
    'ios',
    'android',
    'label',
    'value',
])

<native:column class="flex-1 gap-2 rounded-2xl bg-theme-secondary-surface p-3">
    <native:row class="items-center gap-2">
        <x-native.icon :ios="$ios" :android="$android" :size="18" />
        <native:text class="text-[13] font-semibold text-theme-muted-text">{{ $label }}</native:text>
    </native:row>
    <native:text class="text-[17] font-semibold leading-tight text-theme-primary-text">{{ $value }}</native:text>
</native:column>
