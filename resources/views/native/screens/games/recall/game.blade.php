@php
    $length = count($sequence);
    $enteredCount = count($entered);

    $label = match (true) {
        $feedbackTone === 'correct' => 'Nice!',
        $feedbackTone === 'wrong' => 'Not quite',
        $phase === 'ready' => 'Get ready…',
        $phase === 'watch' => 'Watch',
        default => 'Your turn',
    };
@endphp

<native:column class="h-full w-full bg-theme-background">
    @if ($screenState === 'error')
        <native:column class="flex-1 w-full px-4 items-center justify-center gap-4 safe-area">
            <native:text class="text-[18] font-bold text-center text-theme-primary-text">This game couldn’t start</native:text>
            <native:text class="text-[13] leading-relaxed text-center text-theme-secondary-text">{{ $errorMessage }}</native:text>
            <native:button class="w-full" label="Back to games" size="lg" variant="primary" @press="exit" />
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
        <native:stack class="flex-1 w-full">
            <native:column class="h-full w-full safe-area">
                <native:column class="w-full px-4 pt-3 pb-1 gap-3">
                    <x-native.games.shared.game-hud
                        :lives="$lives"
                        :max-lives="$maxLives"
                        :score="$score"
                        :combo="$combo"
                        :round="$roundIndex + 1"
                        :total="$totalRounds"
                        :motion-duration="$feedbackMotionDuration"
                    />
                </native:column>

                <native:column class="flex-1 w-full items-center justify-center gap-6">
                    <native:column class="items-center gap-2">
                        <native:text
                            native:key="recall-label-{{ $phase }}-{{ $feedbackTone }}-{{ $feedbackSerial }}"
                            class="text-[13] font-semibold uppercase tracking-widest {{ $feedbackTone === 'wrong' ? 'text-theme-danger' : 'text-theme-accent' }}"
                        >
                            {{ $label }}
                        </native:text>

                        <native:row class="items-center gap-1" a11y-label="{{ $enteredCount }} of {{ $length }} tapped">
                            @for ($step = 0; $step < $length; $step++)
                                <native:column class="w-2 h-2 rounded-full {{ $phase === 'recall' && $step < $enteredCount ? 'bg-theme-accent' : 'bg-theme-divider' }}" />
                            @endfor
                        </native:row>
                    </native:column>

                    <x-native.games.recall.tile-grid
                        :tiles="$tiles"
                        :sequence="$sequence"
                        :playback-step="$playbackStep"
                        :phase="$phase"
                        :feedback-tone="$feedbackTone"
                        :last-tile="$lastTile"
                        :tap-serial="$tapSerial"
                        :feedback-serial="$feedbackSerial"
                        :reduced-motion="$reducedMotion"
                        :motion-duration="$feedbackMotionDuration"
                    />
                </native:column>
            </native:column>

            @if ($feedbackTone === 'correct')
                <x-native.games.shared.confetti-burst
                    :serial="$feedbackSerial"
                    :reduced-motion="$reducedMotion"
                    :accent="$accentColor"
                />
            @endif
        </native:stack>
    @endif
</native:column>
