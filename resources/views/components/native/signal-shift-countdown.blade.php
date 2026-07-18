@props([
    'value',
    'rule',
    'round',
    'totalRounds',
    'motionDuration' => 0,
])

<native:column class="h-full w-full items-center justify-center gap-8 bg-theme-background px-6">
    <native:text class="text-xs font-semibold text-theme-accent">
        ROUND {{ $round }} OF {{ $totalRounds }}
    </native:text>

    <native:stack
        native:key="signal-countdown-{{ $value }}"
        class="h-64 w-64 items-center justify-center"
        :scale="$value === 0 ? 1.16 : 1.28"
        :opacity="$motionDuration > 0 ? 0.28 : 1"
        :animate-duration="$motionDuration"
        animate-easing="ease-out"
    >
        <native:circle :width="252" :height="252" class="bg-theme-accent opacity-10" />
        <native:circle :width="196" :height="196" class="border border-theme-accent bg-theme-primary-surface" />
        <native:text
            class="{{ $value === 0 ? 'text-theme-success' : 'text-theme-primary-text' }} text-[88] font-bold"
            a11y-label="{{ $value === 0 ? 'Go' : $value }}"
        >
            {{ $value === 0 ? 'GO' : $value }}
        </native:text>
    </native:stack>

    <native:column class="w-72 items-center gap-2">
        <native:text class="text-xs font-semibold text-theme-muted-text">CURRENT RULE</native:text>
        <native:text class="text-center text-xl font-semibold leading-tight text-theme-primary-text">{{ $rule }}</native:text>
    </native:column>
</native:column>
