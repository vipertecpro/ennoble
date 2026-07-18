@use('App\Enums\ClearThoughtMode')
@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')

<native:column class="safe-area h-full w-full bg-theme-background">
    @if ($screenState === 'error')
        <native:row class="h-full w-full items-center justify-center bg-theme-background px-5">
            <native:column class="w-80">
                <x-native.error-state
                    title="Clear Thought unavailable"
                    :description="$errorMessage"
                    retry-label="Return to workout"
                    retry-method="returnToWorkout"
                />
            </native:column>
        </native:row>
    @else
        <native:scroll-view class="w-full flex-1 bg-theme-background" :shows-indicators="false">
            <native:column class="w-full items-center bg-theme-background px-5">
                <native:column class="w-80 mb-12 mt-6 gap-7">
                    @if ($phase === 'instructions')
                        <native:column class="items-center gap-5">
                            <native:text class="text-[12] font-semibold tracking-widest text-theme-accent">CLEAR THOUGHT · {{ strtoupper($difficulty) }}</native:text>

                            <x-native.clear-thought-hero :motion-duration="$motionDuration" />

                            <native:text class="text-center text-[34] font-bold leading-tight tracking-tight text-theme-primary-text">
                                Say more with less.
                            </native:text>
                            <native:text class="text-center text-[17] leading-relaxed text-theme-secondary-text">
                                Cut the noise, restore the order, and choose the clearest sentence. Take the time you need.
                            </native:text>
                        </native:column>

                        <native:row class="items-center justify-center gap-6">
                            <native:column class="items-center gap-1">
                                <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">{{ $totalRounds }}</native:text>
                                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">SENTENCES</native:text>
                            </native:column>
                            <native:column class="items-center gap-1">
                                <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">3</native:text>
                                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">WAYS</native:text>
                            </native:column>
                            <native:column class="items-center gap-1">
                                <native:text class="text-[22] font-semibold tracking-tight text-theme-primary-text">{{ $maxAttempts }}</native:text>
                                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">{{ $maxAttempts === 1 ? 'ATTEMPT' : 'ATTEMPTS' }}</native:text>
                            </native:column>
                        </native:row>

                        <native:column class="items-center gap-3">
                            <native:button
                                class="w-56"
                                label="Begin Clear Thought"
                                size="lg"
                                variant="primary"
                                @press="beginClearThought"
                            />
                            <native:button class="w-56" label="Back to Workout" size="md" variant="ghost" @press="returnToWorkout" />
                        </native:column>
                    @elseif ($phase === 'challenge')
                        <native:column class="gap-4">
                            <native:row class="w-full items-center justify-between">
                                <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">ROUND {{ $roundNumber }} OF {{ $totalRounds }}</native:text>
                                <native:stack
                                    class="h-11 w-11 items-center justify-center rounded-full bg-theme-secondary-surface"
                                    :press-scale="0.88"
                                    a11y-label="Pause and exit options"
                                    @press="requestExit"
                                >
                                    <x-native.icon
                                        :ios="Ios::Xmark"
                                        :android="AndroidOutlined::Close"
                                        :size="18"
                                    />
                                </native:stack>
                            </native:row>

                            <x-native.clear-thought-progress
                                :outcomes="$roundOutcomes"
                                :current="$roundNumber"
                                :total="$totalRounds"
                                :motion-duration="$motionDuration"
                            />
                        </native:column>

                        <native:column class="gap-2">
                            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">{{ strtoupper($modeLabel) }}</native:text>
                            <native:text class="text-[22] font-semibold leading-tight tracking-tight text-theme-primary-text">{{ $prompt }}</native:text>
                            <native:text class="text-[15] leading-relaxed text-theme-secondary-text">{{ $modeGuidance }}</native:text>
                        </native:column>

                        @if ($mode === ClearThoughtMode::ChooseClearestSentence->value)
                            <native:column class="gap-3">
                                @foreach ($options as $option)
                                    <x-native.clear-thought-option
                                        :option-id="$option['id']"
                                        :text="$option['text']"
                                        :state="$option['state']"
                                        :motion-duration="$motionDuration"
                                    />
                                @endforeach
                            </native:column>
                        @elseif ($mode === ClearThoughtMode::RemoveUnnecessaryWords->value)
                            <native:row class="flex-wrap gap-2">
                                @foreach ($words as $word)
                                    <x-native.clear-thought-word-chip
                                        :word-id="$word['id']"
                                        :text="$word['text']"
                                        :selected="$word['selected']"
                                        :motion-duration="$motionDuration"
                                    />
                                @endforeach
                            </native:row>
                        @elseif ($mode === ClearThoughtMode::ReorderSentence->value)
                            <native:column class="gap-4">
                                <native:column class="min-h-[64] w-full rounded-2xl bg-theme-surface shadow-sm p-3">
                                    @if (count($arranged) === 0)
                                        <native:text class="text-[15] text-theme-muted-text">Your sentence builds here.</native:text>
                                    @else
                                        <native:row class="flex-wrap gap-2">
                                            @foreach ($arranged as $index => $segment)
                                                <x-native.clear-thought-segment-chip
                                                    :segment-id="$segment['id']"
                                                    :text="$segment['text']"
                                                    variant="arranged"
                                                    :position="$index + 1"
                                                    :motion-duration="$motionDuration"
                                                />
                                            @endforeach
                                        </native:row>
                                    @endif
                                </native:column>

                                <native:row class="flex-wrap gap-2">
                                    @foreach ($segments as $segment)
                                        <x-native.clear-thought-segment-chip
                                            :segment-id="$segment['id']"
                                            :text="$segment['text']"
                                            variant="pool"
                                            :used="$segment['used']"
                                            :motion-duration="$motionDuration"
                                        />
                                    @endforeach
                                </native:row>
                            </native:column>
                        @endif

                        <native:column class="min-h-[44] items-center justify-center">
                            @if ($feedbackMessage !== '')
                                <native:text
                                    class="{{ $feedbackTone === 'danger' ? 'text-theme-danger' : 'text-theme-success' }} text-center text-[15] font-semibold"
                                    a11y-label="{{ $feedbackMessage }}"
                                >
                                    {{ $feedbackMessage }}
                                </native:text>
                            @elseif ($hintVisible)
                                <native:text class="text-center text-[15] leading-relaxed text-theme-secondary-text">
                                    {{ $hintText }}
                                </native:text>
                            @endif
                        </native:column>

                        <native:column class="items-center gap-3">
                            @if ($mode === ClearThoughtMode::RemoveUnnecessaryWords->value)
                                <native:button class="w-56" label="Check Sentence" size="lg" variant="primary" @press="submitWords" />
                            @elseif ($mode === ClearThoughtMode::ReorderSentence->value)
                                <native:button class="w-56" label="Check Sentence" size="lg" variant="primary" @press="submitOrder" />
                            @endif

                            @if ($hintText !== '' && ! $hintVisible)
                                <native:button
                                    class="w-56"
                                    label="Use a Hint"
                                    size="md"
                                    variant="ghost"
                                    a11y-hint="Reveals the bundled hint and reduces this round's score"
                                    @press="revealHint"
                                />
                            @endif
                        </native:column>
                    @elseif ($phase === 'reflection')
                        <native:column class="items-center gap-3">
                            <native:text class="text-[12] font-semibold tracking-widest {{ $feedbackTone === 'success' ? 'text-theme-accent' : 'text-theme-warning' }}">
                                {{ $reflectionEyebrow }}
                            </native:text>
                            <native:text class="text-center text-[28] font-bold leading-tight tracking-tight text-theme-primary-text">
                                {{ $reflectionTitle }}
                            </native:text>
                        </native:column>

                        @if ($reflectionAnswer !== '')
                            <native:column
                                class="w-full rounded-2xl bg-theme-primary-surface p-5"
                                :animate-duration="$feedbackMotionDuration"
                                animate-easing="ease-out"
                            >
                                <native:text class="text-[17] font-semibold leading-relaxed text-theme-primary-text">
                                    “{{ $reflectionAnswer }}”
                                </native:text>
                            </native:column>
                        @endif

                        <native:text class="text-center text-[17] leading-relaxed text-theme-secondary-text">
                            {{ $explanation }}
                        </native:text>

                        <x-native.clear-thought-progress
                            :outcomes="$roundOutcomes"
                            :current="$roundNumber"
                            :total="$totalRounds"
                            :motion-duration="$motionDuration"
                        />

                        <native:column class="items-center">
                            <native:button
                                class="w-56"
                                :label="$roundNumber < $totalRounds ? 'Next Sentence' : 'Reveal Results'"
                                size="lg"
                                variant="primary"
                                @press="continueAfterReflection"
                            />
                        </native:column>
                    @elseif ($phase === 'game_result')
                        <native:column class="items-center gap-3">
                            <native:text class="text-[12] font-semibold tracking-widest text-theme-muted-text">
                                {{ $newPersonalBest ? 'PERSONAL BEST' : 'CLEAR THOUGHT COMPLETE' }}
                            </native:text>
                            <native:text class="text-center text-[34] font-bold leading-tight tracking-tight text-theme-primary-text">Clarity held.</native:text>
                            <native:text class="text-center text-[17] leading-relaxed text-theme-secondary-text">{{ $motivationalMessage }}</native:text>
                        </native:column>

                        <x-native.clear-thought-result
                            :score="$resultScore"
                            :accuracy="$resultAccuracy"
                            :response="$resultResponse"
                            :clarity="$resultClarity"
                            :hints="$resultHints"
                            :best-comparison="$bestScoreComparison"
                            :motion-duration="$feedbackMotionDuration"
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
</native:column>
