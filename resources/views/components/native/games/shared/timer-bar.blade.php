@use('Native\Mobile\UI\Theme')

@props([
    'secondsPerRound' => 6,
    'secondsRemaining' => 6,
])

{{-- Native round timer with escalating urgency. The fill runs on the lime accent
     while there is comfortable time, warms to amber past the halfway mark, and
     turns danger-red in the final fifth — real countdown tension. Every stage is
     pulled live from the theme tokens (no hardcoded colours), and the track is
     left to the element's dark-aware `surface-variant` default. Driven by the
     component's per-second poll, so it steps down and re-colours reliably on both
     platforms, and freezes during the answer reveal because the tick stops
     decrementing while awaiting advance. --}}
@php
    $tokens = Theme::all();
    $per = max(1, (int) $secondsPerRound);
    $fraction = max(0.0, min(1.0, ((int) $secondsRemaining) / $per));

    $stage = $fraction > 0.5 ? 'accent' : ($fraction > 0.2 ? 'warning' : 'danger');
    $fill = data_get($tokens, "light.{$stage}", '#F43F5E');
@endphp

<native:progress-bar
    :value="$fraction"
    :color="$fill"
    class="w-full h-2 rounded-full"
    a11y-label="Time remaining this round"
/>
