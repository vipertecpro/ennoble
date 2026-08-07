@use('App\NativeUI\Tokens\Gradients')

@php
    $callout = match ($feedbackTone) {
        'cleared' => 'Clear',
        'hit' => 'Hit',
        default => null,
    };

    $calloutClass = $feedbackTone === 'hit' ? 'text-theme-danger' : 'text-theme-accent';
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
            <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-accent">Leap</native:text>
            <native:text
                native:key="leap-ready-{{ $readyCountdown }}"
                class="text-[64] font-bold tracking-tight text-theme-primary-text"
                :scale="$reducedMotion ? 1 : 1.15"
                :opacity="0.85"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
            >
                {{ max(1, $readyCountdown) }}
            </native:text>
            <native:text class="text-[15] text-center text-theme-secondary-text">
                Tap anywhere to jump. Nothing is timing you — the run ends when you do.
            </native:text>
        </native:column>
    @elseif ($phase === 'result')
        <native:column class="flex-1 w-full safe-area">
            <x-native.games.shared.game-result
                :score="$resultScore"
                :accuracy="$resultAccuracy"
                :best-combo="$resultBestCombo"
                :correct="$resultCorrect"
                :total="$totalObstacles"
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
                    :round="min($resolvedCount + 1, $totalObstacles)"
                    :total="$totalObstacles"
                    :motion-duration="$feedbackMotionDuration"
                />
            </native:column>

            {{-- The whole viewport is the jump button. A runner has exactly one
                 action, and making the player find a control for it is worse
                 than useless — the scene itself takes the tap.

                 The 3D background is the app's own surface token, so the panel
                 sits in the page rather than punching a dark hole in it. The
                 hairline border is what still separates the two. --}}
            <native:column class="flex-1 w-full px-4 py-3">
                <native:pressable class="flex-1 w-full" @press="jump" a11y-label="Jump" a11y-hint="Tap anywhere to jump the next obstacle">
                    <native:scene-3d class="flex-1 w-full rounded-3xl border {{ Gradients::hairline() }}" :scene="$scene" />
                </native:pressable>
            </native:column>

            <native:column class="w-full px-4 pb-6 gap-2">
                <native:text
                    native:key="leap-callout-{{ $feedbackSerial }}"
                    class="text-[12] font-semibold uppercase tracking-widest text-center {{ $calloutClass }}"
                    :animate-duration="$feedbackMotionDuration"
                    animate-easing="ease-out"
                >
                    {{ $callout ?? 'Tap to jump' }}
                </native:text>
            </native:column>
        </native:column>
    @endif
</native:column>
