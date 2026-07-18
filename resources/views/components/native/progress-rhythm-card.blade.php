@props([
    'current' => 0,
    'longest' => 0,
    'weeklyDays' => [],
    'weeklyCompleted' => 0,
    'motionDuration' => 0,
])

<native:column class="w-full items-center rounded-2xl bg-theme-primary-surface py-6" :animate-duration="$motionDuration">
<native:column class="w-full px-4 gap-5">
    <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">TRAINING RHYTHM</native:text>

    @if ($current === 0)
        <native:column class="gap-1">
            <native:text class="text-[22] font-semibold tracking-tight leading-tight text-theme-primary-text">Ready for day one</native:text>
            <native:text class="text-[15] leading-relaxed text-theme-secondary-text">
                Complete today’s workout to begin a streak worth keeping.
            </native:text>
        </native:column>
    @else
        <native:row class="items-end justify-between">
            <native:column class="gap-1">
                <native:text class="text-[44] font-bold tracking-tight leading-tight text-theme-primary-text">{{ $current }}</native:text>
                <native:text class="text-[15] font-semibold text-theme-muted-text">
                    {{ $current === 1 ? 'day in rhythm' : 'days in rhythm' }}
                </native:text>
            </native:column>
            <native:column class="items-end gap-1">
                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">LONGEST</native:text>
                <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">
                    {{ $longest }} {{ $longest === 1 ? 'day' : 'days' }}
                </native:text>
            </native:column>
        </native:row>
    @endif

    <native:column
        class="gap-2"
        a11y-label="Trained on {{ $weeklyCompleted }} of the last 7 days"
    >
        <native:row class="items-center gap-2">
            @foreach ($weeklyDays as $day)
                <native:column class="flex-1 items-center gap-1">
                    <native:column class="w-full h-2 rounded-full {{ $day['active'] ? 'bg-theme-accent' : 'bg-theme-divider' }}" />
                    <native:text class="text-[13] {{ $day['today'] ? 'font-semibold text-theme-primary-text' : 'text-theme-muted-text' }}">
                        {{ $day['label'] }}
                    </native:text>
                </native:column>
            @endforeach
        </native:row>
        <native:text class="text-[15] text-theme-muted-text">{{ $weeklyCompleted }} of the last 7 days</native:text>
    </native:column>
</native:column>
</native:column>
