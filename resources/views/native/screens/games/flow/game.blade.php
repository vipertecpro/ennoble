<native:column class="h-full w-full bg-theme-background">
    @if ($screenState === 'error')
        <native:column class="flex-1 w-full px-4 items-center justify-center gap-4 safe-area">
            <native:text class="text-[18] font-bold text-center text-theme-primary-text">This game couldn’t start</native:text>
            <native:text class="text-[13] leading-relaxed text-center text-theme-secondary-text">{{ $errorMessage }}</native:text>
            <x-native.ui.gradient-button class="w-full" label="Back to games" press="exit" />
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
            {{-- Living aurora background (display-only). --}}
            <x-native.games.flow.aurora-field :reduced-motion="$reducedMotion" />

            {{-- Foreground: swipe capture over the whole lane, above the aurora
                 so it receives the gesture rather than the WebView. --}}
            <native:gesture-area
                @swipe="handleSwipe"
                class="h-full w-full"
                a11y-label="Flow game"
                a11y-hint="Swipe in the direction of the incoming current"
            >
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

                    <x-native.games.flow.current-lane
                        :phase="$phase"
                        :current-direction="$currentDirection"
                        :feedback-tone="$feedbackTone"
                        :feedback-serial="$feedbackSerial"
                        :round-index="$roundIndex"
                        :window-ms="$windowMs"
                        :reduced-motion="$reducedMotion"
                        :motion-duration="$feedbackMotionDuration"
                    />
                </native:column>
            </native:gesture-area>

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
