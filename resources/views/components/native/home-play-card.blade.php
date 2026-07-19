@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

{{-- A prominent "play this game" card for the Home screen. --}}

@props([
    'slug',
    'title',
    'subtitle',
    'pressScale' => 1.0,
    'pressOpacity' => 1.0,
    'motionDuration' => 0,
])

<native:pressable
    class="w-full rounded-2xl bg-theme-surface shadow-sm p-4"
    :press-scale="$pressScale"
    :press-opacity="$pressOpacity"
    :animate-duration="$motionDuration"
    a11y-label="Play {{ $title }}"
    a11y-hint="Opens the {{ $title }} game"
    @press="openGame('{{ $slug }}')"
>
    <native:row class="items-center gap-4">
        <x-native.game-illustration :slug="$slug" :motion-duration="$motionDuration" />
        <native:column class="flex-1 gap-1">
            <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $title }}</native:text>
            <native:text class="text-[13] leading-relaxed text-theme-secondary-text">{{ $subtitle }}</native:text>
        </native:column>
        <x-native.icon
            :ios="Ios::PlayCircle"
            :android="AndroidOutlined::PlayCircle"
            :size="28"
            a11y-label="Play"
        />
    </native:row>
</native:pressable>
