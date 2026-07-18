@props([
    'ios',
    'android',
    'title',
    'description',
    'motionDuration' => 0,
])

<native:column class="h-72 gap-4 rounded-3xl border border-theme-border bg-theme-surface-elevated p-5">
    <x-native.onboarding-illustration
        :ios="$ios"
        :android="$android"
        :a11y-label="$title"
        :motion-duration="$motionDuration"
        compact
    />
    <native:text class="text-xl font-semibold text-theme-primary-text">{{ $title }}</native:text>
    <native:text class="text-sm leading-relaxed text-theme-secondary-text">
        {{ $description }}
    </native:text>
</native:column>
