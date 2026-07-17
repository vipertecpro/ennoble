@use('App\NativeUI\Tokens\DesignTokens')

@props([
    'ios',
    'android',
    'a11yLabel',
    'animated' => false,
    'motionDuration' => 0,
    'compact' => false,
])

<native:column
    class="{{ $compact ? 'w-24 h-24' : 'w-36 h-36' }} items-center justify-center gap-3 rounded-3xl bg-theme-surface-variant"
    :scale="$animated && $motionDuration > 0 ? 1.045 : 1"
    :animate-duration="$motionDuration"
    :animate-loop="$animated && $motionDuration > 0"
    animate-easing="ease-in-out"
>
    <native:row class="items-end gap-2" :opacity="0.62">
        <native:column class="w-3 h-3 rounded-full bg-theme-accent" />
        <native:column class="w-6 h-2 rounded-full bg-theme-primary" />
        <native:column class="w-2 h-5 rounded-full bg-theme-secondary" />
    </native:row>

    <x-native.icon
        :ios="$ios"
        :android="$android"
        :size="$compact ? DesignTokens::ICON_SIZE['large'] : DesignTokens::ICON_SIZE['hero']"
        :a11y-label="$a11yLabel"
    />

    <native:row class="items-center gap-2" :opacity="0.5">
        <native:column class="w-7 h-2 rounded-full bg-theme-secondary" />
        <native:column class="w-2 h-2 rounded-full bg-theme-accent" />
        <native:column class="w-4 h-2 rounded-full bg-theme-primary" />
    </native:row>
</native:column>
