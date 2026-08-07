@use('App\NativeUI\Tokens\Gradients')

@php
    $callout = match ($feedbackTone) {
        'correct' => 'Correct',
        'wrong' => 'That one is the mirror',
        'timeout' => 'Too slow',
        default => null,
    };

    $calloutClass = match ($feedbackTone) {
        'correct' => 'text-theme-accent',
        'wrong', 'timeout' => 'text-theme-danger',
        default => 'text-theme-muted-text',
    };
@endphp

<native:column class="h-full w-full {{ Gradients::screen() }}">
    @if ($screenState === 'error')
        <native:column class="flex-1 w-full px-4 items-center justify-center gap-4 safe-area">
            <native:text class="text-[18] font-bold text-center text-theme-primary-text">This game couldn’t start</native:text>
            <native:text class="text-[13] leading-relaxed text-center text-theme-secondary-text">{{ $errorMessage }}</native:text>
            <x-native.ui.gradient-button class="w-full" label="Back to games" press="exit" />
        </native:column>
    @elseif ($phase === 'ready')
        <native:column class="flex-1 w-full px-4 items-center justify-center gap-6 safe-area">
            <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-accent">Axis</native:text>
            <native:text
                native:key="axis-ready-{{ $readyCountdown }}"
                class="text-[64] font-bold tracking-tight text-theme-primary-text"
                :scale="$reducedMotion ? 1 : 1.15"
                :opacity="0.85"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
            >
                {{ max(1, $readyCountdown) }}
            </native:text>
            <native:text class="text-[15] text-center text-theme-secondary-text">
                One of the two is the same solid, turned. The other is its mirror.
            </native:text>
        </native:column>
    @elseif ($phase === 'result')
        <native:column class="flex-1 w-full safe-area">
            <x-native.games.shared.game-result
                :score="$resultScore"
                :accuracy="$resultAccuracy"
                :best-combo="$resultBestCombo"
                :correct="$resultCorrect"
                :total="$totalRounds"
                :is-new-best="$isNewBest"
                :motion-duration="$feedbackMotionDuration"
                :reduced-motion="$reducedMotion"
            />
        </native:column>
    @else
        <native:column class="h-full w-full safe-area">
            <native:column class="w-full px-4 pt-3 gap-3">
                <x-native.games.shared.game-hud
                    :lives="$lives"
                    :max-lives="$maxLives"
                    :score="$score"
                    :combo="$combo"
                    :round="$roundIndex + 1"
                    :total="$totalRounds"
                    :motion-duration="$feedbackMotionDuration"
                />

                <native:column
                    class="w-full items-center rounded-2xl px-4 py-2.5 border bg-theme-accent/15 border-theme-accent/40"
                    a11y-label="Tap the solid that is the same shape as the one above"
                >
                    <native:text class="text-[14] font-bold uppercase tracking-widest text-theme-accent">
                        Tap the match
                    </native:text>
                </native:column>
            </native:column>

            {{-- The viewport is the game. It fills what is left so the solids
                 are as large as the screen allows — a mental rotation gets
                 measurably harder when the figure is small, and that is
                 difficulty the task did not ask for. --}}
            <native:column class="flex-1 w-full px-4 py-3">
                <native:scene-3d class="flex-1 w-full rounded-3xl" :scene="$scene" @tap="chooseFigure" />
            </native:column>

            <native:column class="w-full px-4 pb-6 gap-2">
                <native:text
                    native:key="axis-callout-{{ $feedbackSerial }}"
                    class="text-[12] font-semibold uppercase tracking-widest text-center {{ $calloutClass }}"
                    :animate-duration="$feedbackMotionDuration"
                    animate-easing="ease-out"
                >
                    {{ $callout ?? $cubeCount.' cubes' }}
                </native:text>

                <x-native.games.shared.timer-bar
                    :seconds-per-round="$secondsPerRound"
                    :seconds-remaining="$secondsRemaining"
                />
            </native:column>
        </native:column>
    @endif
</native:column>
