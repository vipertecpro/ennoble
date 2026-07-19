@props([
    'score' => 0,
    'accuracy' => null,
    'bestCombo' => 0,
    'correct' => 0,
    'total' => 0,
    'isNewBest' => false,
    'motionDuration' => 0,
    'reducedMotion' => false,
])

{{-- End-of-game score report: a headline score that pops in, three stat tiles,
     and the play-again / done actions. --}}
<native:column class="flex-1 w-full px-4 items-center justify-center gap-7">
    <native:column class="w-full items-center gap-1">
        <native:text class="text-[13] font-semibold uppercase tracking-widest text-theme-accent">
            {{ $isNewBest ? 'New best score' : 'Session complete' }}
        </native:text>
        <native:text
            native:key="word-match-result-{{ $score }}"
            class="text-[64] font-bold tracking-tight text-theme-primary-text"
            :scale="$reducedMotion ? 1 : 1.1"
            :opacity="0.85"
            :animate-duration="$motionDuration"
            animate-easing="ease-out"
        >
            {{ number_format($score) }}
        </native:text>
        <native:text class="text-[15] text-theme-secondary-text">points</native:text>
    </native:column>

    <native:row class="w-full gap-3">
        <native:column class="flex-1 items-center gap-1 rounded-2xl bg-theme-surface shadow-sm py-4">
            <native:text class="text-[22] font-bold text-theme-primary-text">{{ $accuracy === null ? '—' : round($accuracy).'%' }}</native:text>
            <native:text class="text-[12] text-theme-muted-text">Accuracy</native:text>
        </native:column>
        <native:column class="flex-1 items-center gap-1 rounded-2xl bg-theme-surface shadow-sm py-4">
            <native:text class="text-[22] font-bold text-theme-primary-text">{{ $correct }}/{{ $total }}</native:text>
            <native:text class="text-[12] text-theme-muted-text">Correct</native:text>
        </native:column>
        <native:column class="flex-1 items-center gap-1 rounded-2xl bg-theme-surface shadow-sm py-4">
            <native:text class="text-[22] font-bold text-theme-primary-text">×{{ $bestCombo }}</native:text>
            <native:text class="text-[12] text-theme-muted-text">Best combo</native:text>
        </native:column>
    </native:row>

    <native:column class="w-full gap-2">
        <native:button class="w-full" label="Play again" size="lg" variant="primary" @press="playAgain" />
        <native:button class="w-full" label="Done" size="md" variant="ghost" @press="exit" />
    </native:column>
</native:column>
