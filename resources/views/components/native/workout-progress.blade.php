@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'currentGame',
    'gamesRemaining',
    'progress',
    'timeEstimate',
])

<native:column
    class="w-full gap-3 rounded-2xl bg-theme-surface p-4"
    a11y-label="{{ $currentGame }}. {{ $gamesRemaining }} games remaining. {{ (int) round($progress * 100) }} percent complete. {{ $timeEstimate }}"
>
    <native:row class="w-full items-center gap-3">
        <native:row class="flex-1 items-center gap-2">
            <x-native.icon :ios="Ios::Brain" :android="AndroidOutlined::Psychology" :size="18" />
            <native:text class="text-sm font-semibold text-theme-on-surface">{{ $currentGame }}</native:text>
        </native:row>
        <native:text class="text-sm font-semibold text-theme-primary">{{ (int) round($progress * 100) }}%</native:text>
    </native:row>

    <native:progress-bar
        :value="$progress"
        a11y-label="Workout progress, {{ (int) round($progress * 100) }} percent"
    />

    <native:row class="w-full flex-wrap items-center gap-4">
        <native:row class="items-center gap-2">
            <x-native.icon :ios="Ios::ListNumber" :android="AndroidOutlined::FormatListNumbered" :size="18" />
            <native:text class="text-sm text-theme-on-surface-variant">
                {{ $gamesRemaining }} {{ $gamesRemaining === 1 ? 'game' : 'games' }} remaining
            </native:text>
        </native:row>
        <native:row class="items-center gap-2">
            <x-native.icon :ios="Ios::Clock" :android="AndroidOutlined::Timer" :size="18" />
            <native:text class="text-sm text-theme-on-surface-variant">{{ $timeEstimate }}</native:text>
        </native:row>
    </native:row>
</native:column>
