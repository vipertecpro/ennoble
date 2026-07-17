@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

@props([
    'game',
    'pressScale' => 1.0,
    'pressOpacity' => 1.0,
    'motionDuration' => 0,
])

<native:pressable
    class="w-full gap-4 rounded-3xl bg-theme-surface p-5"
    :press-scale="$pressScale"
    :press-opacity="$pressOpacity"
    a11y-label="{{ $game['title'] }}, {{ $game['category'] }}, coming soon"
    a11y-hint="Opens information about this unavailable future game"
    @press="showComingSoon('{{ $game['slug'] }}')"
>
    <native:row class="w-full items-center gap-4">
        <x-native.game-illustration :slug="$game['slug']" :motion-duration="$motionDuration" />
        <native:column class="flex-1 gap-2">
            <native:row class="w-full flex-wrap items-center gap-2">
                <x-native.game-badge label="COMING SOON" :emphasis="true" :motion-duration="$motionDuration" />
                <native:text class="text-xs font-semibold text-theme-on-surface-variant">{{ $game['category'] }}</native:text>
            </native:row>
            <native:text class="text-xl font-bold leading-tight text-theme-on-surface">{{ $game['title'] }}</native:text>
            <native:row class="items-center gap-2">
                <x-native.icon :ios="Ios::Clock" :android="AndroidOutlined::Timer" :size="18" />
                <native:text class="text-sm font-semibold text-theme-on-surface-variant">{{ $game['duration'] }}</native:text>
            </native:row>
        </native:column>
    </native:row>

    <native:text class="text-sm leading-relaxed text-theme-on-surface-variant">{{ $game['description'] }}</native:text>
</native:pressable>
