@use('App\NativeUI\Tokens\Gradients')

@php
    // Two per row keeps every tile inside thumb reach at 3 and 4 options alike.
    $optionRows = array_chunk($options, 2);

    $callout = match ($feedbackTone) {
        'correct' => 'Correct',
        'wrong' => 'Wrong signal',
        'timeout' => 'Too slow',
        default => null,
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
            <native:text class="text-[12] font-semibold uppercase tracking-widest text-theme-accent">Signal</native:text>
            <native:text
                native:key="signal-ready-{{ $readyCountdown }}"
                class="text-[64] font-bold tracking-tight text-theme-primary-text"
                :scale="$reducedMotion ? 1 : 1.15"
                :opacity="0.85"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
            >
                {{ max(1, $readyCountdown) }}
            </native:text>
            <native:text class="text-[15] text-center text-theme-secondary-text">
                Read the rule before you answer — it changes.
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

                    {{-- The bar freezes during the reveal because the tick stops
                         decrementing while awaiting advance. --}}
                    <x-native.games.shared.timer-bar
                        :seconds-per-round="$secondsPerRound"
                        :seconds-remaining="$secondsRemaining"
                    />
                </native:column>

                <native:column class="flex-1 w-full px-4 items-center justify-center gap-4">
                    <x-native.games.signal.stimulus
                        :rule="$rule"
                        :word="$word"
                        :ink="$ink"
                        :rule-switched="$ruleSwitched"
                        :round-index="$roundIndex"
                        :feedback-tone="$feedbackTone"
                        :reduced-motion="$reducedMotion"
                        :motion-duration="$motionDuration"
                    />

                    {{-- One line that carries the round counter while a round is
                         live and the verdict once it resolves — no layout shift
                         between the two. --}}
                    <native:text
                        native:key="signal-callout-{{ $feedbackSerial }}"
                        class="text-[12] font-semibold uppercase tracking-widest {{ match ($feedbackTone) {
                            'correct' => 'text-theme-accent',
                            'idle' => 'text-theme-muted-text',
                            default => 'text-theme-danger',
                        } }}"
                        :animate-duration="$feedbackMotionDuration"
                        animate-easing="ease-out"
                    >
                        {{ $callout ?? 'Round '.($roundIndex + 1).' of '.$totalRounds }}
                    </native:text>
                </native:column>

                <native:column class="w-full px-4 pb-8 gap-3">
                    @foreach ($optionRows as $row)
                        <native:row class="w-full gap-3">
                            @foreach ($row as $option)
                                <x-native.games.signal.option
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
