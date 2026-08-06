@use('App\NativeUI\Tokens\DesignTokens')
@use('App\NativeUI\Tokens\Gradients')

{{--
    Onboarding hero badge — a vibrant, layered mark rather than a flat tile.
    A soft CIRCULAR glow (rounded-full) sits behind a SQUIRCLE icon chip
    (rounded-3xl): two different shapes so the badge reads as depth and motion.
    Each step passes its own `tone`, so the flow moves through the neon ramp
    (lime → cyan → violet → amber → lime) and feels alive. Pure Tailwind palette
    stops with `to-transparent`, so the glow adapts to the theme background and
    stays vibrant in both light and dark.
--}}

@props([
    'ios',
    'android',
    'a11yLabel',
    'tone' => 'lime',
    'animated' => false,
    'motionDuration' => 0,
    'compact' => false,
])

@php
    // Per-step tone hues come from config/native-ui.php (the single source of
    // truth). The glow carries the colour; the icon stays high-contrast primary
    // ink so it reads crisply in both modes.
    [$from, $via] = Gradients::onboardingTone($tone);

    $frame = $compact ? 'w-28 h-28' : 'w-32 h-32';
    $chip = $compact ? 'w-16 h-16' : 'w-20 h-20';
    $iconSize = $compact ? DesignTokens::ICON_SIZE['large'] : DesignTokens::ICON_SIZE['hero'];
@endphp

<native:stack class="{{ $frame }} items-center justify-center">
    {{-- Vibrant circular glow — a different shape from the chip it cradles. --}}
    <native:column class="{{ $frame }} rounded-full bg-linear-to-br from-{{ $from }}/55 via-{{ $via }}/25 to-transparent" />

    {{-- Squircle icon chip — crisp accent-tinted glyph for vibrance. --}}
    <native:column
        class="{{ $chip }} items-center justify-center rounded-3xl bg-theme-surface-elevated border {{ Gradients::hairline() }} shadow-lg"
        :scale="$animated && $motionDuration > 0 ? 0.96 : 1"
        :animate-duration="$motionDuration"
        animate-easing="ease-out"
    >
        <x-native.ui.icon
            :ios="$ios"
            :android="$android"
            :size="$iconSize"
            :a11y-label="$a11yLabel"
        />
    </native:column>
</native:stack>
