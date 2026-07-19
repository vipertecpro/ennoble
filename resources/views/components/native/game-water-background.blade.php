@props([
    'secondsRemaining' => 0,
    'secondsPerRound' => 1,
    'motionDuration' => 0,
])

{{--
    Full-screen "water level" round timer. A tinted fill rises with the round
    and drains a step each second (the level), while the body and its bright
    "waterline" surface bob on offset loop periods so the water gently sloshes
    rather than sitting dead-still. Real ripples need the Lottie renderer; this
    is the shape-based approximation. Sits behind the gameplay as a native:stack
    back layer.
--}}
@php
    $fraction = $secondsPerRound > 0
        ? max(0.0, min(1.0, $secondsRemaining / $secondsPerRound))
        : 0.0;
    $waterPx = max(6, (int) round($fraction * 760));
    $animated = $motionDuration > 0;
@endphp

<native:column class="h-full w-full items-stretch justify-end" a11y-label="{{ $secondsRemaining }} seconds left this round">
    <native:column
        class="w-full rounded-t-2xl bg-theme-primary-surface h-[{{ $waterPx }}px]"
        :translate-y="$animated ? -6 : 0"
        :animate-duration="1400"
        :animate-loop="$animated"
        animate-easing="ease-in-out"
    >
        <native:column
            class="w-full h-[4px] rounded-full bg-theme-accent"
            :opacity="0.55"
            :translate-y="$animated ? 4 : 0"
            :animate-duration="1100"
            :animate-loop="$animated"
            animate-easing="ease-in-out"
        />
    </native:column>
</native:column>
