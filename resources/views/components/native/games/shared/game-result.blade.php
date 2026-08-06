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

{{-- End-of-game score report: a celebratory gradient hero with the headline
     score that pops in, three glowing stat tiles, and the play-again / done
     actions. --}}
<native:column class="flex-1 w-full px-4 items-center justify-center gap-7">
    <x-native.ui.glow-card accent="lime-400" class="w-full items-center gap-1 p-7">
        <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-accent">
            {{ $isNewBest ? 'New best score' : 'Session complete' }}
        </native:text>
        <native:text
            native:key="word-match-result-{{ $score }}"
            class="text-[52] font-bold tracking-tight text-theme-primary-text"
            :scale="$reducedMotion ? 1 : 1.1"
            :opacity="0.85"
            :animate-duration="$motionDuration"
            animate-easing="ease-out"
        >
            {{ number_format($score) }}
        </native:text>
        <native:text class="text-[13] text-theme-secondary-text">points</native:text>
    </x-native.ui.glow-card>

    <native:row class="w-full gap-3">
        <x-native.ui.stat-badge
            :value="$accuracy === null ? '—' : round($accuracy).'%'"
            label="Accuracy"
            accent="lime-400"
            accentTo="cyan-500"
            labelColor="lime-400"
        />
        <x-native.ui.stat-badge
            :value="$correct.'/'.$total"
            label="Correct"
            accent="cyan-500"
            accentTo="lime-400"
            labelColor="cyan-400"
        />
        <x-native.ui.stat-badge
            :value="'×'.$bestCombo"
            label="Best combo"
            accent="amber-400"
            accentTo="orange-500"
            labelColor="amber-400"
        />
    </native:row>

    <native:column class="w-full gap-2">
        <x-native.ui.gradient-button label="Play again" press="playAgain" />
        <native:button class="w-full" label="Done" size="md" variant="ghost" @press="exit" />
    </native:column>
</native:column>
