@props([
    'score',
    'accuracy',
    'response',
    'clarity',
    'hints',
    'bestComparison',
    'motionDuration' => 0,
])

<native:column class="w-full items-center gap-6" :animate-duration="$motionDuration" animate-easing="ease-out">
    <native:column class="items-center gap-1">
        <native:text
            class="text-[44] font-bold leading-tight tracking-tight text-theme-primary-text"
            a11y-label="Final score {{ $score }} points"
        >
            {{ $score }}
        </native:text>
        <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">POINTS</native:text>
    </native:column>

    <native:row class="w-full items-start justify-center gap-8">
        <native:column class="items-center gap-1">
            <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">{{ $accuracy }}</native:text>
            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">ACCURACY</native:text>
        </native:column>
        <native:column class="items-center gap-1">
            <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">{{ $response }}</native:text>
            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">AVG RESPONSE</native:text>
        </native:column>
    </native:row>

    <native:column class="items-center gap-1">
        <native:text class="text-[17] font-semibold text-theme-primary-text">{{ $clarity }} · {{ $hints }}</native:text>
        <native:text class="text-[15] text-theme-secondary-text">{{ $bestComparison }}</native:text>
    </native:column>
</native:column>
