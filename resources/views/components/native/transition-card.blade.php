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
    class="w-full gap-5 rounded-3xl bg-theme-surface p-5"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $previousGame }} complete. Next game {{ $nextGame }}."
>
    <native:row class="w-full items-center gap-4">
        <native:column class="items-center justify-center rounded-full bg-theme-primary p-4">
            <x-native.icon
                :ios="Ios::Checkmark"
                :android="AndroidOutlined::Check"
                :size="28"
                a11y-label="Game complete"
            />
        </native:column>
        <native:column class="flex-1 gap-1">
            <native:text class="text-xs font-semibold text-theme-primary">GAME COMPLETE</native:text>
            <native:text class="text-2xl font-bold leading-tight text-theme-on-surface">{{ $previousGame }}</native:text>
        </native:column>
    </native:row>

    <native:column class="w-full gap-2 rounded-2xl bg-theme-surface-variant p-4">
        <native:text class="text-xs font-semibold text-theme-on-surface-variant">PERFORMANCE</native:text>
        <native:text class="text-base leading-relaxed text-theme-on-surface">{{ $performanceMessage }}</native:text>
    </native:column>

    <native:column class="w-full gap-2">
        <native:text class="text-xs font-semibold text-theme-primary">UP NEXT</native:text>
        <native:text class="text-2xl font-bold text-theme-on-surface">{{ $nextGame }}</native:text>
        <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">
            @if ($autoTransitionEnabled)
                Continuing automatically in {{ $autoTransitionSeconds }} {{ $autoTransitionSeconds === 1 ? 'second' : 'seconds' }}.
            @else
                Automatic transition is off while Reduced Motion is enabled. Continue when ready.
            @endif
        </native:text>
    </native:column>
</native:column>
