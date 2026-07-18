@props([
    'outcomes' => [],
    'current' => 0,
    'total' => 0,
    'motionDuration' => 0,
])

<native:row
    class="w-full items-center gap-2"
    a11y-label="Round {{ max($current, count($outcomes)) }} of {{ $total }}. {{ collect($outcomes)->filter(fn ($outcome) => $outcome === 'correct')->count() }} clear so far."
>
    @for ($round = 1; $round <= $total; $round++)
        @php
            $outcome = $outcomes[$round - 1] ?? null;
            $markerClass = match (true) {
                $outcome === 'correct' => 'bg-theme-accent',
                $outcome === 'incorrect' => 'bg-theme-warning',
                $round === $current => 'bg-theme-secondary-text',
                default => 'bg-theme-divider',
            };
        @endphp
        <native:column
            native:key="clear-thought-round-{{ $round }}"
            class="h-2 flex-1 rounded-full {{ $markerClass }}"
            :animate-duration="$motionDuration"
        />
    @endfor
</native:row>
