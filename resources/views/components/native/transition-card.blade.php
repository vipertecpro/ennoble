@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'previousGame',
    'nextGame',
    'performanceMessage',
    'autoTransitionEnabled',
    'autoTransitionSeconds',
    'motionDuration' => 0,
])

<native:column
    class="w-80 items-center rounded-3xl border border-theme-border bg-theme-surface-elevated py-5"
    :animate-duration="$motionDuration"
    :a11y-label="$previousGame.' complete. Next game '.$nextGame.'.'"
>
<native:column class="w-72 gap-5">
    <native:row class="items-center gap-4">
        <native:column class="items-center justify-center rounded-2xl bg-theme-primary-surface p-4">
            <x-native.icon
                :ios="Ios::Checkmark"
                :android="AndroidOutlined::Check"
                :size="28"
                a11y-label="Game complete"
            />
        </native:column>
        <native:column class="flex-1 gap-1">
            <native:text class="text-xs font-semibold text-theme-accent">GAME COMPLETE</native:text>
            <native:text class="text-2xl font-bold leading-tight text-theme-primary-text">{{ $previousGame }}</native:text>
        </native:column>
    </native:row>

    <native:column class="gap-2 rounded-2xl bg-theme-secondary-surface p-4">
        <native:text class="text-xs font-semibold text-theme-muted-text">PERFORMANCE</native:text>
        <native:text class="text-base leading-relaxed text-theme-primary-text">{{ $performanceMessage }}</native:text>
    </native:column>

    <native:column class="gap-2">
        <native:text class="text-xs font-semibold text-theme-accent">UP NEXT</native:text>
        <native:text class="text-2xl font-bold text-theme-primary-text">{{ $nextGame }}</native:text>
        <native:text class="text-sm leading-relaxed text-theme-secondary-text">
            @if ($autoTransitionEnabled)
                Continuing automatically in {{ $autoTransitionSeconds }} {{ $autoTransitionSeconds === 1 ? 'second' : 'seconds' }}.
            @else
                Automatic transition is off while Reduced Motion is enabled. Continue when ready.
            @endif
        </native:text>
    </native:column>
</native:column>
</native:column>
