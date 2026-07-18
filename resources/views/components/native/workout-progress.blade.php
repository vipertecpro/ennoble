@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'currentGame',
    'gamesRemaining',
    'progress',
    'timeEstimate',
    'steps' => [],
])

<native:column
    class="gap-4 rounded-2xl bg-theme-primary-surface shadow-sm p-4"
    a11y-label="Workout rhythm. {{ $currentGame }}. {{ $gamesRemaining }} {{ $gamesRemaining === 1 ? 'game' : 'games' }} remaining. {{ $timeEstimate }}"
>
    <native:row class="items-center justify-between gap-4">
        <native:column class="flex-1 gap-1">
            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">WORKOUT RHYTHM</native:text>
            <native:text class="text-[17] font-semibold text-theme-primary-text">{{ $currentGame }}</native:text>
        </native:column>
        <native:text class="text-[13] font-semibold text-theme-muted-text">{{ $timeEstimate }}</native:text>
    </native:row>

    <native:row class="items-start justify-around gap-3">
        @foreach ($steps as $step)
            <native:column class="flex-1 items-center gap-2" :native:key="$step['position'].'-'.$step['label']">
                <native:column
                    class="{{ $step['state'] === 'completed' ? 'border-theme-accent bg-theme-accent' : ($step['state'] === 'current' ? 'border-theme-accent bg-theme-surface-elevated' : 'border-theme-border bg-theme-secondary-surface') }} h-12 w-12 items-center justify-center rounded-full border-2"
                >
                    @if ($step['state'] === 'completed')
                        <x-native.icon :ios="Ios::Checkmark" :android="AndroidOutlined::Check" :size="20" />
                    @else
                        <native:text class="{{ $step['state'] === 'current' ? 'text-theme-accent' : 'text-theme-muted-text' }} text-[15] font-semibold">
                            {{ $step['position'] }}
                        </native:text>
                    @endif
                </native:column>
                <native:text class="{{ $step['state'] === 'current' ? 'text-theme-primary-text' : 'text-theme-muted-text' }} text-center text-[13] font-semibold">
                    {{ $step['label'] }}
                </native:text>
            </native:column>
        @endforeach
    </native:row>
</native:column>
