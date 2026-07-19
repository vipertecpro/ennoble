@props([
    'secondsRemaining' => 0,
    'secondsPerRound' => 1,
    'motionDuration' => 0,
    'reducedMotion' => false,
])

{{--
    Water-glass round timer. A tall rounded "glass" holds a bottom-anchored
    water column whose height tracks the remaining time and drains a step each
    second. The water shifts accent → warning → danger as time runs low.
    (Absolute h-[Npx] heights are the only reliable fill mechanism in the
    native UI — there is no canvas/Lottie, so the level steps rather than
    ripples.)
--}}
@php
    $fraction = $secondsPerRound > 0
        ? max(0.0, min(1.0, $secondsRemaining / $secondsPerRound))
        : 0.0;
    $tube = 200;
    $waterPx = max(10, (int) round($fraction * ($tube - 8)));
    $fill = $fraction > 0.5
        ? 'bg-theme-accent'
        : ($fraction > 0.25 ? 'bg-theme-warning' : 'bg-theme-danger');
@endphp

<native:column
    class="w-9 h-[{{ $tube }}px] items-center justify-end rounded-full bg-theme-secondary-surface p-1"
    a11y-label="{{ $secondsRemaining }} seconds remaining this round"
>
    <native:column
        class="w-full h-[{{ $waterPx }}px] rounded-full {{ $fill }}"
        :opacity="$reducedMotion ? 1 : 0.92"
        :animate-duration="$motionDuration"
        animate-easing="ease-out"
    />
</native:column>
