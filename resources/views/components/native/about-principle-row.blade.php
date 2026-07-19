@props([
    'title',
    'description',
    'ios',
    'android',
])

<native:row class="items-start gap-4 p-5">
    <native:column class="items-center justify-center rounded-xl bg-theme-secondary-surface p-3">
        <x-native.icon :ios="$ios" :android="$android" :size="24" />
    </native:column>
    <native:column class="flex-1 gap-1">
        <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $title }}</native:text>
        <native:text class="text-[13] leading-relaxed text-theme-secondary-text">{{ $description }}</native:text>
    </native:column>
</native:row>
