@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="safe-area h-full w-full bg-theme-background">
    @if ($screenState === 'error')
        <native:row class="h-full w-full items-center justify-center bg-theme-background px-5">
            <native:column class="w-80">
                <x-native.error-state
                    title="Signal Shift unavailable"
                    :description="$errorMessage"
                    retry-label="Return to workout"
                    retry-method="returnToWorkout"
                />
            </native:column>
        </native:row>
    @elseif ($phase === 'round_countdown')
        <x-native.signal-shift-countdown
            :value="$roundCountdown"
            :rule="$ruleText"
            :round="$gameRound"
            :total-rounds="$totalRounds"
            :motion-duration="$countdownMotionDuration"
        />
    @elseif ($phase === 'playing')
        <native:column class="w-full flex-1 gap-2 bg-theme-background px-4 py-3">
            <x-native.signal-shift-hud
                :rule="$ruleText"
                :timer="$roundSecondsRemaining"
                :score="$score"
                :lives="$lives"
                :max-lives="$maxLives"
                :combo="$feedbackTone === 'success' ? $combo : 0"
                :progress="$progress"
                :game-round="$gameRound"
                :wave="$wave"
                :wave-count="$waveCount"
                :show-pause="true"
                :motion-duration="$motionDuration"
            />

            <x-native.signal-shift-playfield
                :stimuli="$stimuli"
                :resolved-stimulus-ids="$resolvedStimulusIds"
                :feedback-tone="$feedbackTone"
                :feedback-message="$feedbackMessage"
                :feedback-serial="$feedbackSerial"
                :floating-score="$floatingScore"
                :combo="$combo"
                :motion-duration="$motionDuration"
                :feedback-motion-duration="$feedbackMotionDuration"
            />

            <native:column class="h-14 w-full items-center justify-center">
                @if ($feedbackTone !== 'neutral')
                    <native:text
                        class="{{ $feedbackTone === 'danger' ? 'text-theme-danger' : 'text-theme-success' }} text-center text-[15] font-semibold"
                        a11y-label="{{ $feedbackMessage }}"
                    >
                        {{ $feedbackMessage }}
                    </native:text>
                @else
                    <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">
                        {{ $waveSecondsRemaining }} SEC WINDOW
                    </native:text>
                @endif
            </native:column>
        </native:column>
    @else
        <native:scroll-view class="w-full flex-1 bg-theme-background" :shows-indicators="false">
            <native:column class="w-full items-center bg-theme-background px-5">
                <native:column class="w-80 mb-12 mt-6 gap-7">
                    @if ($phase === 'instructions')
                        <native:column class="items-center gap-5">
                            <native:text class="text-[12] font-semibold tracking-widest text-theme-accent">SIGNAL SHIFT · {{ strtoupper($difficulty) }}</native:text>

                            <x-native.signal-shift-hero :motion-duration="$motionDuration" />

                            <native:text class="text-center text-[34] font-bold leading-tight tracking-tight text-theme-primary-text">
                                Follow the rule. Ignore the noise.
                            </native:text>
                            <native:text class="text-center text-[17] leading-relaxed text-theme-secondary-text">
                                Three rapid rule shifts. Three lives. Find the one shape that truly belongs.
                            </native:text>
                        </native:column>

                        <native:row class="items-center justify-center gap-6">
                            <native:column class="items-center gap-1">
                                <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">3</native:text>
                                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">ROUNDS</native:text>
                            </native:column>
                            <native:column class="items-center gap-1">
                                <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">{{ $maxLives }}</native:text>
                                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">LIVES</native:text>
                            </native:column>
                            <native:column class="items-center gap-1">
                                <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">1</native:text>
                                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">SIGNAL</native:text>
                            </native:column>
                        </native:row>

                        <native:column class="items-center gap-3">
                            <native:button
                                class="w-56"
                                :label="$tutorialRequired ? 'Learn the Signal' : 'Play Signal Shift'"
                                size="lg"
                                variant="primary"
                                @press="beginSignalShift"
                            />
                            @unless ($tutorialRequired)
                                <native:button class="w-56" label="Practice Tutorial" size="md" variant="ghost" @press="practiceTutorial" />
                            @endunless
                            <native:button class="w-56" label="Back to Workout" size="md" variant="ghost" @press="returnToWorkout" />
                        </native:column>
                    @elseif ($phase === 'tutorial')
                        <native:column class="items-center gap-3">
                            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">PRACTICE · NO SCORE</native:text>
                            <native:text class="text-center text-[28] font-bold leading-tight tracking-tight text-theme-primary-text">Find the signal</native:text>
                            <native:text class="text-center text-[17] font-semibold leading-tight text-theme-primary-text">{{ $ruleText }}</native:text>
                        </native:column>

                        <x-native.signal-shift-playfield
                            :stimuli="$stimuli"
                            :resolved-stimulus-ids="$resolvedStimulusIds"
                            :feedback-tone="$feedbackTone"
                            :feedback-message="$tutorialFeedback"
                            :motion-duration="$motionDuration"
                            :feedback-motion-duration="$feedbackMotionDuration"
                            :tutorial="true"
                        />

                        <native:text
                            class="{{ $feedbackTone === 'danger' ? 'text-theme-danger' : ($feedbackTone === 'success' ? 'text-theme-success' : 'text-theme-secondary-text') }} text-center text-[17] font-semibold leading-relaxed"
                            a11y-label="{{ $tutorialFeedback }}"
                        >
                            {{ $tutorialFeedback }}
                        </native:text>

                        <native:column class="items-center">
                            @if ($tutorialComplete)
                                <native:button class="w-56" label="Enter Round 1" size="lg" variant="primary" @press="skipTutorial" />
                            @else
                                <native:button class="w-56" label="Skip Practice" size="md" variant="ghost" @press="skipTutorial" />
                            @endif
                        </native:column>
                    @elseif ($phase === 'round_intro')
                        <native:column class="min-h-[640] items-center justify-center gap-8">
                            <native:column class="items-center gap-2">
                                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">
                                    ROUND {{ $gameRound }} OF {{ $totalRounds }}
                                </native:text>
                                <native:text class="text-center text-[28] font-bold leading-tight tracking-tight text-theme-primary-text">
                                    {{ $gameRound === 1 ? 'Lock onto the signal' : 'Rule shift' }}
                                </native:text>
                            </native:column>

                            <x-native.signal-shift-rule-reveal
                                :rule="$ruleText"
                                :round="$gameRound"
                                :motion-duration="$motionDuration"
                            />

                            <native:text class="text-center text-[17] leading-relaxed text-theme-secondary-text">{{ $feedbackMessage }}</native:text>

                            <native:column class="items-center gap-3">
                                <native:button class="w-56" label="Ready" size="lg" variant="primary" @press="startRound" />
                                <native:button class="w-56" label="Exit Game" size="md" variant="ghost" @press="requestExit" />
                            </native:column>
                        </native:column>
                    @elseif ($phase === 'round_result' || $phase === 'failed')
                        <native:column class="items-center gap-3">
                            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">
                                {{ $phase === 'failed' ? 'SIGNAL LOST' : 'ROUND '.$gameRound.' COMPLETE' }}
                            </native:text>
                            <native:text class="text-center text-[28] font-bold leading-tight tracking-tight text-theme-primary-text">
                                {{ $phase === 'failed' ? 'Reset. Read. React.' : 'Attention held.' }}
                            </native:text>
                            <native:text class="text-center text-[17] leading-relaxed text-theme-secondary-text">{{ $motivationalMessage }}</native:text>
                        </native:column>

                        <x-native.signal-shift-result
                            :accuracy="$roundAccuracy"
                            :reaction-time="$roundReactionTime"
                            :score="$roundScore"
                            :lives="$roundLives"
                            :best-comparison="$bestScoreComparison"
                            :combo="$bestCombo"
                            :motion-duration="$feedbackMotionDuration"
                        />

                        <native:column class="items-center gap-3">
                            @if ($phase === 'failed')
                                <native:button class="w-56" label="Play Again" size="lg" variant="primary" @press="restartGame" />
                                <native:button class="w-56" label="Exit Workout" size="md" variant="ghost" @press="requestExit" />
                            @else
                                <native:button
                                    class="w-56"
                                    :label="$gameRound < $totalRounds ? 'Next Rule' : 'Reveal Results'"
                                    size="lg"
                                    variant="primary"
                                    @press="continueAfterRound"
                                />
                            @endif
                        </native:column>
                    @elseif ($phase === 'game_result')
                        <native:column class="items-center gap-3">
                            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">
                                {{ $newPersonalBest ? 'PERSONAL BEST' : 'SIGNAL SHIFT COMPLETE' }}
                            </native:text>
                            <native:text class="text-center text-[34] font-bold leading-tight tracking-tight text-theme-primary-text">Signal mastered.</native:text>
                            <native:text class="text-center text-[17] leading-relaxed text-theme-secondary-text">{{ $motivationalMessage }}</native:text>
                        </native:column>

                        <x-native.signal-shift-result
                            :accuracy="$roundAccuracy"
                            :reaction-time="$roundReactionTime"
                            :score="$roundScore"
                            :lives="$roundLives"
                            :best-comparison="$bestScoreComparison"
                            :combo="$bestCombo"
                            :motion-duration="$feedbackMotionDuration"
                            :final="true"
                            :new-personal-best="$newPersonalBest"
                        />

                        <native:column class="items-center gap-3">
                            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">SAVED PRIVATELY ON THIS DEVICE</native:text>
                            <native:button
                                class="w-56"
                                label="Continue Workout"
                                size="lg"
                                variant="primary"
                                :loading="$isSubmitting"
                                :disabled="$isSubmitting"
                                @press="continueWorkout"
                            />
                        </native:column>
                    @endif
                </native:column>
            </native:column>
        </native:scroll-view>
    @endif

    @if ($dialogVisible)
        @include('native.partials.workout-exit-dialog')
    @endif

    @if ($bottomSheetVisible)
        @include('native.partials.signal-shift-pause-sheet')
    @endif
</native:column>
