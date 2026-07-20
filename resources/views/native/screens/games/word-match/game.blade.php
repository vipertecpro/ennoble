<native:gesture-area @swipe="handleSwipe" class="h-full w-full" a11y-label="Word Match game" a11y-hint="Swipe right to leave the game">
<native:column class="h-full w-full bg-theme-background">
    @if ($screenState === 'error')
        <native:column class="flex-1 w-full px-4 items-center justify-center gap-4">
            <native:text class="text-[18] font-bold text-center text-theme-primary-text">This game couldn’t start</native:text>
            <native:text class="text-[13] leading-relaxed text-center text-theme-secondary-text">{{ $errorMessage }}</native:text>
            <native:button class="w-full" label="Back to games" size="lg" variant="primary" @press="exit" />
        </native:column>
    @elseif ($phase === 'ready')
        <native:column class="flex-1 w-full px-4 items-center justify-center gap-6">
            <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-accent">Word Match</native:text>
            <native:text
                native:key="word-match-ready-{{ $readyCountdown }}"
                class="text-[64] font-bold tracking-tight text-theme-primary-text"
                :scale="$reducedMotion ? 1 : 1.15"
                :opacity="0.85"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
            >
                {{ max(1, $readyCountdown) }}
            </native:text>
            <native:text class="text-[15] text-center text-theme-secondary-text">Get ready…</native:text>
        </native:column>
    @elseif ($phase === 'result')
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
    @else
        <native:stack class="flex-1 w-full">
            {{-- The draining water is the timer; hide it the moment an answer
                 is locked in so it freezes instead of running through the reveal. --}}
            @unless ($awaitingAdvance)
                <x-native.games.shared.water-timer
                    :seconds-per-round="$secondsPerRound"
                    :seconds-remaining="$secondsRemaining"
                    :round-key="$roundIndex"
                />
            @endunless

            <native:column class="h-full w-full safe-area">
                <native:column class="w-full px-4 pt-3 pb-1">
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

            <native:column class="flex-1 w-full px-4 items-center justify-center gap-3">
                    <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-muted-text">
                        Find the {{ strtoupper($relation) }}
                    </native:text>
                    <native:text
                        native:key="word-match-prompt-{{ $roundIndex }}"
                        class="w-full text-[30] font-bold tracking-tight leading-tight text-center text-theme-primary-text"
                        :translate-y="$reducedMotion ? 0 : 6"
                        :opacity="0.9"
                        :animate-duration="$motionDuration"
                        animate-easing="ease-out"
                    >
                        {{ $prompt }}
                    </native:text>
                    <native:text class="text-[11] text-theme-muted-text">Round {{ $roundIndex + 1 }} of {{ $totalRounds }}</native:text>
                </native:column>

                <native:column class="w-full px-4 pb-8 gap-3">
                    @foreach ($options as $option)
                        <x-native.games.word-match.option
                            :option="$option"
                            :answer="$answer"
                            :selected="$selectedOption"
                            :tone="$feedbackTone"
                            :serial="$feedbackSerial"
                            :reduced-motion="$reducedMotion"
                            :motion-duration="$feedbackMotionDuration"
                        />
                    @endforeach
                </native:column>
            </native:column>
        </native:stack>
    @endif
</native:column>
</native:gesture-area>
