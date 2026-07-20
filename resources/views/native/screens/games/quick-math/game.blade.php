<native:column class="h-full w-full bg-theme-background">
    @if ($screenState === 'error')
        <native:column class="flex-1 w-full px-4 items-center justify-center gap-4 safe-area">
            <native:text class="text-[18] font-bold text-center text-theme-primary-text">This game couldn’t start</native:text>
            <native:text class="text-[13] leading-relaxed text-center text-theme-secondary-text">{{ $errorMessage }}</native:text>
            <native:button class="w-full" label="Back to games" size="lg" variant="primary" @press="exit" />
        </native:column>
    @elseif ($phase === 'ready')
        <native:column class="flex-1 w-full px-4 items-center justify-center gap-6 safe-area">
            <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-accent">Quick Math</native:text>
            <native:text
                native:key="quick-math-ready-{{ $readyCountdown }}"
                class="text-[64] font-bold tracking-tight text-theme-primary-text"
                :scale="$reducedMotion ? 1 : 1.15"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
            >
                {{ max(1, $readyCountdown) }}
            </native:text>
            <native:text class="text-[15] text-center text-theme-secondary-text">Get ready…</native:text>
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

                    <x-native.games.shared.timer-bar
                        :seconds-per-round="$secondsPerRound"
                        :seconds-remaining="$secondsRemaining"
                    />
                </native:column>

                <native:column class="flex-1 w-full px-4 items-center justify-center gap-6">
                    <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-muted-text">Solve</native:text>

                    <x-native.games.quick-math.equation
                        :expression="$expression"
                        :answer="$answer"
                        :typed="$typedAnswer"
                        :tone="$feedbackTone"
                        :serial="$feedbackSerial"
                        :reduced-motion="$reducedMotion"
                        :motion-duration="$feedbackMotionDuration"
                    />

                    @if ($awaitingContinue)
                        <native:column
                            native:key="qm-reveal-{{ $feedbackSerial }}"
                            class="items-center gap-1 pt-2"
                            :translate-y="$reducedMotion ? 0 : 10"
                            :opacity="$reducedMotion ? 1 : 0.85"
                            :animate-duration="$motionDuration"
                            animate-easing="ease-out"
                        >
                            <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">
                                {{ $feedbackTone === 'timeout' ? 'Time’s up' : 'Not quite' }}
                            </native:text>
                            <native:text class="text-[13] text-theme-secondary-text">Correct answer</native:text>
                            <native:text class="text-[30] font-bold tracking-tight text-theme-accent">{{ number_format($answer) }}</native:text>
                        </native:column>
                    @endif
                </native:column>

                @if ($awaitingContinue)
                    <native:row class="w-full px-4 pb-6 items-start">
                        <native:button label="Explain" size="lg" variant="secondary" @press="openExplain" />
                        <native:spacer />
                        <native:column class="w-[160] gap-2 items-center">
                            <native:button class="w-full" label="Continue" size="lg" variant="primary" @press="continueRound" />
                            <native:progress-bar
                                :value="$continueTotal > 0 ? $continueTicks / $continueTotal : 0"
                                color="#C5DB55"
                                class="w-full"
                                a11y-label="Auto-continuing"
                            />
                        </native:column>
                    </native:row>
                @else
                    <x-native.games.quick-math.keypad
                        :disabled="$awaitingAdvance"
                        :reduced-motion="$reducedMotion"
                    />
                @endif
            </native:column>

            @if ($feedbackTone === 'correct')
                <x-native.games.shared.confetti-burst
                    :serial="$feedbackSerial"
                    :reduced-motion="$reducedMotion"
                />
            @endif
        </native:stack>
    @endif
</native:column>
