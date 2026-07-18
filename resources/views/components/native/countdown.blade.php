@props([
    'count',
    'announcement',
    'coaching' => null,
    'motionDuration' => 0,
])

<native:column
    class="items-center gap-5 py-5"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    :a11y-label="$announcement"
>
    <native:stack class="h-52 w-52 items-center justify-center">
        <native:circle :width="208" :height="208" class="bg-theme-accent opacity-10" />
        <native:circle :width="152" :height="152" class="border-2 border-theme-accent bg-theme-background" />
        <native:column class="h-52 w-52 items-center justify-center gap-1">
            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">SETTLE</native:text>
            <native:text class="text-[44] font-bold tracking-tight text-theme-primary-text" :a11y-label="$announcement">
                {{ $count > 0 ? $count : 'GO' }}
            </native:text>
        </native:column>
    </native:stack>

    @if ($coaching)
        <native:text class="text-center text-[17] font-semibold leading-relaxed text-theme-primary-text">
            {{ $coaching }}
        </native:text>
    @endif
    <native:text class="text-center text-[15] leading-relaxed text-theme-secondary-text">
        {{ $count > 0 ? 'One breath before the next challenge.' : 'Your focus is ready.' }}
    </native:text>
</native:column>
