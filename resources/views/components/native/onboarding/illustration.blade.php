@use('App\NativeUI\Tokens\DesignTokens')

{{--
    Cortex onboarding hero — a single, symmetric, premium badge that sits
    directly on the calm background. A soft lime halo behind an elevated
    squircle carrying one crisp icon. No washed-out panel, no muddy rings.
    Theme-safe: the halo is the accent tint, the badge is the elevated
    surface, the icon is high-contrast primary ink in both themes.
--}}

@props([
    'ios',
    'android',
    'a11yLabel',
    'animated' => false,
    'motionDuration' => 0,
    'compact' => false,
])

<native:stack class="{{ $compact ? 'w-32 h-32' : 'w-44 h-44' }} items-center justify-center">
    <native:column
        class="{{ $compact ? 'w-28 h-28' : 'w-40 h-40' }} rounded-full bg-theme-primary-surface"
    />
    <native:column
        class="{{ $compact ? 'w-20 h-20' : 'w-28 h-28' }} items-center justify-center rounded-3xl bg-theme-surface-elevated shadow-sm"
        :scale="$animated && $motionDuration > 0 ? 0.96 : 1"
        :animate-duration="$motionDuration"
        animate-easing="ease-out"
    >
        <x-native.ui.icon
            :ios="$ios"
            :android="$android"
            :size="$compact ? DesignTokens::ICON_SIZE['large'] : DesignTokens::ICON_SIZE['hero']"
            :a11y-label="$a11yLabel"
        />
    </native:column>
</native:stack>
