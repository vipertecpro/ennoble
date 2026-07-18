@props([
    'accuracy',
    'reactionTime',
    'score',
    'lives',
    'bestComparison',
    'combo',
    'motionDuration' => 0,
    'final' => false,
    'newPersonalBest' => false,
])

<native:column class="w-full items-center gap-6">
    <native:stack
        native:key="result-score-{{ $score }}"
        class="h-48 w-48 items-center justify-center"
        :scale="$final ? 1.06 : 1"
        :animate-duration="$motionDuration"
        animate-easing="ease-out"
    >
        <native:circle :width="184" :height="184" class="bg-theme-accent opacity-10" />
        <native:circle :width="152" :height="152" class="border border-theme-accent bg-theme-primary-surface" />
        <native:column class="items-center gap-1">
            <native:text class="text-xs font-semibold text-theme-accent">
                {{ $newPersonalBest ? 'NEW BEST' : 'SCORE' }}
            </native:text>
            <native:text class="text-5xl font-bold text-theme-primary-text">{{ $score }}</native:text>
            <native:text class="text-xs font-semibold text-theme-muted-text">POINTS</native:text>
        </native:column>
    </native:stack>

    <native:text
        class="text-center text-base font-semibold leading-relaxed text-theme-accent"
        a11y-label="{{ $bestComparison }}"
    >
        {{ $bestComparison }}
    </native:text>

    <native:column class="w-full items-center gap-5">
        <native:row class="w-full items-start justify-between gap-6">
            <native:column class="flex-1 items-center gap-1">
                <native:text class="text-2xl font-bold text-theme-primary-text">{{ $accuracy }}</native:text>
                <native:text class="text-center text-xs font-semibold text-theme-muted-text">ACCURACY</native:text>
            </native:column>
            <native:column class="flex-1 items-center gap-1">
                <native:text class="text-center text-2xl font-bold text-theme-primary-text">{{ $reactionTime }}</native:text>
                <native:text class="text-center text-xs font-semibold text-theme-muted-text">REACTION</native:text>
            </native:column>
        </native:row>

        <native:column class="w-full items-center gap-1">
            <native:text class="text-2xl font-bold text-theme-primary-text">x{{ $combo }}</native:text>
            <native:text class="text-center text-xs font-semibold text-theme-muted-text">BEST COMBO</native:text>
        </native:column>
    </native:column>

    <native:text class="text-sm text-theme-muted-text">{{ $lives }} lives held</native:text>
</native:column>
