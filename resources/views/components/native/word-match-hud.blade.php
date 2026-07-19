@use('App\Icons\Ios')
@use('App\Icons\Android')

@props([
    'lives' => 3,
    'maxLives' => 3,
    'score' => 0,
    'combo' => 0,
    'motionDuration' => 0,
])

{{-- Top HUD: a close control (the way back out), lives, and the live score
     with a combo pill. The round timer lives in the water glass, not here. --}}
<native:row class="w-full items-center justify-between">
    <native:pressable
        @press="exit"
        a11y-label="Close game"
        :press-scale="0.9"
        class="w-9 h-9 items-center justify-center rounded-full bg-theme-surface"
    >
        <x-native.icon :ios="Ios::Xmark" :android="Android::Close" :size="16" />
    </native:pressable>

    <native:row class="items-center gap-2" a11y-label="{{ $lives }} of {{ $maxLives }} lives remaining">
        @for ($life = 1; $life <= $maxLives; $life++)
            <native:column class="w-2 h-2 rounded-full {{ $life <= $lives ? 'bg-theme-primary-text' : 'bg-theme-divider' }}" />
        @endfor
    </native:row>

    <native:row class="items-center gap-2">
        @if ($combo >= 2)
            <native:column
                native:key="combo-{{ $combo }}"
                class="rounded-full bg-theme-primary-surface px-2 py-1"
                :scale="1.08"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
                a11y-label="Combo times {{ $combo }}"
            >
                <native:text class="text-[11] font-bold text-theme-accent">×{{ $combo }}</native:text>
            </native:column>
        @endif
        <native:text class="text-[15] font-bold text-theme-primary-text">{{ number_format($score) }}</native:text>
    </native:row>
</native:row>
