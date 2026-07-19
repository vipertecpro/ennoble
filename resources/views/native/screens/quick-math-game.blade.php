<native:gesture-area @swipe="handleSwipe" class="h-full w-full" a11y-label="Quick Math game" a11y-hint="Swipe right to leave the game">
<native:column class="h-full w-full bg-theme-background">
    @if ($screenState === 'error')
        <native:column class="flex-1 w-full px-4 items-center justify-center gap-4">
            <native:text class="text-[22] font-bold text-center text-theme-primary-text">This game couldn’t start</native:text>
            <native:text class="text-[15] leading-relaxed text-center text-theme-secondary-text">{{ $errorMessage }}</native:text>
            <native:button class="w-full" label="Back to games" size="lg" variant="primary" @press="exit" />
        </native:column>
    @elseif ($phase === 'ready')
        <native:column class="flex-1 w-full px-4 items-center justify-center gap-6">
            <native:text class="text-[13] font-semibold uppercase tracking-widest text-theme-accent">Quick Math</native:text>
            <native:text
                native:key="quick-math-ready-{{ $readyCountdown }}"
                class="text-[80] font-bold tracking-tight text-theme-primary-text"
                :scale="$reducedMotion ? 1 : 1.15"
                :opacity="0.85"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
            >
                {{ max(1, $readyCountdown) }}
            </native:text>
            <native:text class="text-[17] text-center text-theme-secondary-text">Get ready…</native:text>
        </native:column>
    @elseif ($phase === 'result')
        <x-native.word-match-result
            :score="$resultScore"
            :accuracy="$resultAccuracy"
            :best-combo="$resultBestCombo"
            :correct="$resultCorrect"
            :total="$totalRounds"
            :is-new-best="$isNewBest"
            :motion-duration="$feedbackMotionDuration"
            :reduced-motion="$reducedMotion"
        />
    @else
        <native:column class="w-full px-4 pt-3 pb-2">
            <x-native.word-match-hud
                :lives="$lives"
                :max-lives="$maxLives"
                :score="$score"
                :combo="$combo"
                :motion-duration="$feedbackMotionDuration"
            />
        </native:column>

        <native:row class="flex-1 w-full px-4 items-center justify-center gap-4">
            <native:column class="flex-1 items-center justify-center gap-3">
                <native:text class="text-[13] font-semibold uppercase tracking-widest text-theme-muted-text">Solve</native:text>
                <native:text
                    native:key="quick-math-prompt-{{ $roundIndex }}"
                    class="w-full text-[44] font-bold tracking-tight leading-tight text-center text-theme-primary-text"
                    :translate-y="$reducedMotion ? 0 : 6"
                    :opacity="0.9"
                    :animate-duration="$motionDuration"
                    animate-easing="ease-out"
                >
                    {{ $expression }}
                </native:text>
                <native:text class="text-[12] text-theme-muted-text">Round {{ $roundIndex + 1 }} of {{ $totalRounds }}</native:text>
            </native:column>

            <x-native.word-match-timer
                :seconds-remaining="$secondsRemaining"
                :seconds-per-round="$secondsPerRound"
                :motion-duration="$motionDuration"
                :reduced-motion="$reducedMotion"
            />
        </native:row>

        <native:column class="w-full px-4 pb-8 gap-3">
            @foreach (array_chunk($options, 2) as $optionRow)
                <native:row class="w-full items-stretch gap-3">
                    @foreach ($optionRow as $option)
                        <x-native.quick-math-option
                            :option="$option"
                            :answer="$answer"
                            :selected="$selectedOption"
                            :tone="$feedbackTone"
                            :serial="$feedbackSerial"
                            :reduced-motion="$reducedMotion"
                            :motion-duration="$feedbackMotionDuration"
                        />
                    @endforeach
                </native:row>
            @endforeach
        </native:column>
    @endif
</native:column>
</native:gesture-area>
