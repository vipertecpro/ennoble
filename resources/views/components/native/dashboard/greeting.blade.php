@props([
    'date' => '',
    'greeting',
    'displayName',
    'message' => null,
    'motionDuration' => 0,
])

{{-- Home header — plain text on the page background (no card: no fill, no
     rounding, no shadow) so it merges seamlessly with the top. Bold two-line
     greeting; the player's name pops in the lime accent for a game-like feel. --}}
<native:column
    class="w-full gap-1"
    :translate-y="$motionDuration > 0 ? -2 : 0"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $greeting }}, {{ $displayName }}"
>
    @if ($date)
        <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-muted-text">{{ $date }}</native:text>
    @endif
    <native:text class="text-[30] font-bold tracking-tight leading-tight text-theme-primary-text">{{ $greeting }},</native:text>
    <native:text class="text-[30] font-bold tracking-tight leading-tight text-theme-accent">{{ $displayName }}</native:text>
    @if ($message)
        <native:text class="text-[13] leading-relaxed text-theme-secondary-text">{{ $message }}</native:text>
    @endif
</native:column>
