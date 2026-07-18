@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'duration',
    'gamesCompleted',
    'coaching',
    'bestMomentTitle',
    'bestMomentDetail',
    'motionDuration' => 0,
])

<native:column
    class="w-full items-center gap-7 py-6"
    :animate-duration="$motionDuration"
    :a11y-label="'Workout complete. '.$gamesCompleted.' games completed in '.$duration.'. '.$coaching"
>
    <native:stack class="h-60 w-60 items-center justify-center">
        <native:circle :width="240" :height="240" class="bg-theme-accent opacity-10" />
        <native:circle :width="176" :height="176" class="bg-theme-accent opacity-14" />
        <native:circle :width="112" :height="112" class="border-2 border-theme-accent bg-theme-background" />
        <native:column class="h-60 w-60 items-center justify-center">
            <x-native.icon :ios="Ios::CheckmarkSeal" :android="AndroidOutlined::CheckCircle" :size="48" />
        </native:column>
    </native:stack>

    <native:column class="items-center gap-3">
        <native:text class="text-[12] font-semibold tracking-widest text-theme-accent">DAILY MOMENTUM COMPLETE</native:text>
        <native:text class="text-center text-[34] font-bold tracking-tight leading-tight text-theme-primary-text">Workout complete.</native:text>
        <native:text class="text-center text-[17] font-semibold leading-tight text-theme-primary-text">{{ $coaching }}</native:text>
        <native:text class="text-center text-[15] leading-relaxed text-theme-secondary-text">
            {{ $gamesCompleted }} focused steps · {{ $duration }}
        </native:text>
    </native:column>

    <native:column class="w-full items-center gap-2 rounded-2xl bg-theme-primary-surface shadow-sm p-5">
        <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">BEST MOMENT</native:text>
        <native:text class="text-center text-[17] font-semibold text-theme-primary-text">{{ $bestMomentTitle }}</native:text>
        <native:text class="text-center text-[15] leading-relaxed text-theme-secondary-text">{{ $bestMomentDetail }}</native:text>
    </native:column>
</native:column>
