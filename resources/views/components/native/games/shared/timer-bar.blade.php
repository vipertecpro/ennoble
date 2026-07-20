@props([
    'secondsPerRound' => 6,
    'secondsRemaining' => 6,
])

{{-- Native round timer: a determinate progress bar that empties as the round
     clock runs down. Driven by the component's per-second poll (secondsRemaining
     changes each tick), so it re-renders and steps down reliably on both
     platforms. It naturally freezes during the answer reveal because the tick
     stops decrementing while awaiting advance. --}}
@php
    $per = max(1, (int) $secondsPerRound);
    $fraction = max(0.0, min(1.0, ((int) $secondsRemaining) / $per));
@endphp

<native:progress-bar
    :value="$fraction"
    color="#C5DB55"
    class="w-full"
    a11y-label="Time remaining this round"
/>
