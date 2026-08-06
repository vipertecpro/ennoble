@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

{{-- Home hero: the recently-played game as a bold, tappable card with a glossy
     gradient Play CTA (reference "Race Now" energy). The whole card opens the
     game; the pill is the visual call to action. --}}

@props([
    'slug',
    'title',
    'subtitle',
    'pressScale' => 1.0,
    'pressOpacity' => 1.0,
    'motionDuration' => 0,
])

<native:pressable
    class="w-full rounded-3xl bg-linear-to-br from-lime-400/40 via-cyan-500/18 to-transparent border border-lime-400/50 shadow-lg p-5"
    :press-scale="$pressScale"
    :press-opacity="$pressOpacity"
    :animate-duration="$motionDuration"
    a11y-label="Play {{ $title }}"
    a11y-hint="Opens the {{ $title }} game"
    @press="openGame('{{ $slug }}')"
>
    <native:column class="w-full gap-4">
        <native:row class="items-center gap-4">
            <x-native.games.shared.illustration :slug="$slug" :motion-duration="$motionDuration" :animated="true" />
            <native:column class="flex-1 gap-1">
                <native:text class="text-[19] font-bold tracking-tight text-theme-primary-text">{{ $title }}</native:text>
                <native:text class="text-[13] leading-relaxed text-theme-secondary-text">{{ $subtitle }}</native:text>
            </native:column>
        </native:row>

        {{-- Glossy gradient Play CTA (the whole card is tappable; this is the
             visual call to action). --}}
        <native:column class="w-full rounded-full bg-linear-to-r from-lime-400 to-cyan-400 shadow-lg py-3 items-center">
            <native:text class="text-[15] font-bold tracking-tight text-black">Play</native:text>
        </native:column>
    </native:column>
</native:pressable>
