@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\Gradients')
@props([
    'score' => 0,
    'accuracy' => null,
    'bestCombo' => 0,
    'correct' => 0,
    'total' => 0,
    'isNewBest' => false,
    'motionDuration' => 0,
    'reducedMotion' => false,
])

{{-- End-of-game score report: a celebratory gradient hero with the headline
     score that pops in, three glowing stat tiles, and the play-again / done
     actions side by side.

     Type here is deliberately a step down from the app's display sizes: the
     three stat tiles have to hold a value AND a wide uppercase label
     ("BEST COMBO") inside one row of thirds, and at the larger sizes the label
     spilled past the tile's rounded edge on iOS. Compact value/label sizes and
     tighter padding keep every tile's content inside its own frame. --}}
<native:column class="flex-1 w-full px-4 items-center gap-5">
    <native:spacer />
    <native:column class="w-full rounded-3xl bg-linear-to-br from-rose-500/25 via-rose-500/10 to-transparent border {{ Gradients::hairline() }} items-center gap-1 p-6">
        <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-accent">
            {{ $isNewBest ? 'New best score' : 'Session complete' }}
        </native:text>
        <native:text
            native:key="game-result-score-{{ $score }}"
            class="text-[40] font-bold tracking-tight text-theme-primary-text"
            :scale="$reducedMotion ? 1 : 1.1"
            :opacity="0.85"
            :animate-duration="$motionDuration"
            animate-easing="ease-out"
        >
            {{ number_format($score) }}
        </native:text>
        <native:text class="text-[12] text-theme-secondary-text">points</native:text>
    </native:column>

    <native:row class="w-full items-center gap-3">
        <x-native.games.shared.result-stat
            :value="$accuracy === null ? '—' : round($accuracy).'%'"
            label="Accuracy"
            accent="rose-500"
            labelColor="rose-500"
        />
        <x-native.games.shared.result-stat
            :value="$correct.'/'.$total"
            label="Correct"
            accent="cyan-500"
            labelColor="cyan-400"
        />
        <x-native.games.shared.result-stat
            :value="'×'.$bestCombo"
            label="Best combo"
            accent="amber-400"
            labelColor="amber-400"
        />
    </native:row>

    <native:row class="w-full items-center gap-3">
        <x-native.ui.gradient-button
            label="Play again"
            press="playAgain"
            width="flex-1"
            :ios-icon="Ios::ArrowClockwise"
            :android-icon="AndroidOutlined::Refresh"
        />
        <x-native.ui.gradient-button
            label="Done"
            press="exit"
            variant="ghost"
            width="flex-1"
            :ios-icon="Ios::Checkmark"
            :android-icon="AndroidOutlined::Check"
        />
    </native:row>

    <native:spacer />
</native:column>
