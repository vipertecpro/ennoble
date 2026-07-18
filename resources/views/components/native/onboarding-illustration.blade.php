@use('App\NativeUI\Tokens\DesignTokens')

@props([
    'ios',
    'android',
    'a11yLabel',
    'animated' => false,
    'motionDuration' => 0,
    'compact' => false,
])

<native:stack
    class="w-80 {{ $compact ? 'h-40' : 'h-72' }} rounded-3xl bg-theme-primary-surface"
>
    <native:column
        class="{{ $compact ? 'w-28 h-28' : 'w-52 h-52' }} rounded-full bg-theme-selected"
        :opacity="0.72"
        :scale="$animated && $motionDuration > 0 ? 1.06 : 1"
        :animate-duration="$motionDuration"
        :animate-loop="$animated && $motionDuration > 0"
        animate-easing="ease-in-out"
    />
    <native:column
        class="{{ $compact ? 'w-20 h-20' : 'w-36 h-36' }} items-center justify-center rounded-full border border-theme-border bg-theme-surface-elevated"
        :translate-y="$animated && $motionDuration > 0 ? -4 : 0"
        :animate-duration="$motionDuration"
        :animate-loop="$animated && $motionDuration > 0"
        animate-easing="ease-in-out"
    >
        <x-native.icon
            :ios="$ios"
            :android="$android"
            :size="$compact ? DesignTokens::ICON_SIZE['large'] : 64"
            :a11y-label="$a11yLabel"
        />
    </native:column>
</native:stack>
