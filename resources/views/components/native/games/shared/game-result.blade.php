@use('App\NativeUI\Tokens\Gradients')
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
    <native:column class="w-full rounded-3xl bg-linear-to-br from-rose-500/25 via-rose-500/10 to-transparent border {{ Gradients::hairline() }} items-center gap-1 p-7">
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
    </native:column>

    <native:row class="w-full items-stretch gap-3">
        <x-native.ui.stat-badge
            :value="$accuracy === null ? '—' : round($accuracy).'%'"
            label="Accuracy"
            accent="rose-500"
            accentTo="cyan-500"
            labelColor="rose-500"
        />
        <x-native.ui.stat-badge
            :value="$correct.'/'.$total"
            label="Correct"
            accent="cyan-500"
            accentTo="rose-500"
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
