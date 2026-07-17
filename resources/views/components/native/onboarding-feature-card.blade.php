@props([
    'ios',
    'android',
    'title',
    'description',
    'motionDuration' => 0,
])

<native:column class="h-72 gap-4 rounded-3xl bg-theme-surface p-5">
    <x-native.onboarding-illustration
        :ios="$ios"
        :android="$android"
        :a11y-label="$title"
        :motion-duration="$motionDuration"
        compact
    />
    <native:text class="text-xl font-semibold text-theme-on-surface">{{ $title }}</native:text>
    <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
        {{ $description }}
    </native:text>
</native:column>
