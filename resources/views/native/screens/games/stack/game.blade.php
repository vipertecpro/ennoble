@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\Gradients')

@php
    $callout = match ($feedbackTone) {
        'quad' => 'Four at once',
        'clear' => 'Line cleared',
        'buried' => 'Buried a cell',
        'topped' => 'Stack topped out',
        default => null,
    };

    $calloutClass = match ($feedbackTone) {
        'quad', 'clear' => 'text-theme-accent',
        'buried', 'topped' => 'text-theme-danger',
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
            <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-accent">Stack</native:text>
            <native:text
                native:key="stack-ready-{{ $readyCountdown }}"
                class="text-[64] font-bold tracking-tight text-theme-primary-text"
                :scale="$reducedMotion ? 1 : 1.15"
                :opacity="0.85"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
            >
                {{ max(1, $readyCountdown) }}
            </native:text>
            <native:text class="text-[15] text-center text-theme-secondary-text">
                Fill a row to clear it. Never bury an empty cell — you cannot reach it again.
            </native:text>
        </native:column>
    @elseif ($phase === 'result')
        <native:column class="flex-1 w-full safe-area">
            <x-native.games.shared.game-result
                :score="$resultScore"
                :accuracy="$resultAccuracy"
                :best-combo="$resultBestCombo"
                :correct="$resultCorrect"
                :total="$totalPieces"
                :is-new-best="$isNewBest"
                :motion-duration="$feedbackMotionDuration"
                :reduced-motion="$reducedMotion"
            />
        </native:column>
    @else
        <native:column class="h-full w-full safe-area">
            <native:column class="w-full px-4 pt-3 gap-2">
                <x-native.games.shared.game-hud
                    :lives="$lives"
                    :max-lives="$maxLives"
                    :score="$score"
                    :combo="$combo"
                    :round="$pieceIndex + 1"
                    :total="$totalPieces"
                    :motion-duration="$feedbackMotionDuration"
                    :show-lives="false"
                />
            </native:column>

            <native:column class="flex-1 w-full px-4 py-2">
                <native:scene-3d class="flex-1 w-full rounded-3xl border {{ Gradients::hairline() }}" :scene="$scene" />
            </native:column>

            <native:text
                native:key="stack-callout-{{ $feedbackSerial }}"
                class="text-[12] font-semibold uppercase tracking-widest text-center {{ $calloutClass }}"
                :animate-duration="$feedbackMotionDuration"
                animate-easing="ease-out"
            >
                {{ $callout ?? $lines.' lines' }}
            </native:text>

            {{-- Buttons, not gestures. Every control here is a discrete move,
                 and a button says exactly what it does and cannot be misread
                 as a swipe the way a drag across a board would be. --}}
            <native:row class="w-full px-4 pt-2 pb-6 gap-2 items-center">
                <native:button class="flex-1" variant="secondary" size="lg" icon="chevron.left" a11y-label="Move left" @press="moveLeft" />
                <native:button class="flex-1" variant="secondary" size="lg" icon="arrow.clockwise" a11y-label="Rotate" @press="rotate" />
                <native:button class="flex-1" variant="secondary" size="lg" icon="chevron.down" a11y-label="Soft drop" @press="softDrop" />
                <native:button class="flex-1" variant="primary" size="lg" icon="arrow.down.to.line" a11y-label="Hard drop" @press="hardDrop" />
                <native:button class="flex-1" variant="secondary" size="lg" icon="chevron.right" a11y-label="Move right" @press="moveRight" />
            </native:row>
        </native:column>
    @endif
</native:column>
