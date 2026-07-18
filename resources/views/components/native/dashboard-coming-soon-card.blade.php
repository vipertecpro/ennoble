@props([
    'experience',
    'title',
    'description',
    'ios',
    'android',
    'pressScale' => 1.0,
    'pressOpacity' => 1.0,
])

<native:pressable
    class="p-4"
    :press-scale="$pressScale"
    :press-opacity="$pressOpacity"
    a11y-label="{{ $title }}, coming soon"
    a11y-hint="Opens information about this future experience"
    @press="showComingSoon('{{ $experience }}')"
>
    <native:row class="items-center gap-4">
        <native:column class="items-center justify-center rounded-2xl bg-theme-secondary-surface p-3">
            <x-native.icon :ios="$ios" :android="$android" :size="24" />
        </native:column>
        <native:column class="flex-1 gap-1">
            <native:row class="items-center gap-2">
                <native:text class="flex-1 text-base font-semibold text-theme-primary-text">{{ $title }}</native:text>
                <native:text class="text-xs font-semibold text-theme-accent">COMING SOON</native:text>
            </native:row>
            <native:text class="text-sm leading-relaxed text-theme-secondary-text">{{ $description }}</native:text>
        </native:column>
    </native:row>
</native:pressable>
