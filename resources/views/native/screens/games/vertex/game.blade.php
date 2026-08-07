@use('App\NativeUI\Tokens\Gradients')

@php
    $live = $phase === 'wave';

    [$callout, $calloutTone] = match ($feedbackTone) {
        'swept' => ['Wave swept', 'good'],
        'breached' => ['Wrong target', 'bad'],
        'landed' => ['Formation landed', 'bad'],
        default => [$phase === 'ready' ? 'Stand by…' : 'Wave '.($waveIndex + 1).' of '.$totalWaves, 'idle'],
    };

    $calloutClass = match ($calloutTone) {
        'good' => 'text-theme-accent',
        'bad' => 'text-theme-danger',
        default => 'text-theme-muted-text',
    };

    // The descent bar doubles as the threat gauge: it empties as the formation
    // closes, so the player reads time-left and danger from the same object.
    $descentFraction = $descentMs > 0 ? max(0, $descentRemainingMs) / $descentMs : 0;
@endphp

<native:column class="h-full w-full {{ Gradients::screen() }}">
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
                :total="$totalWaves"
                :is-new-best="$isNewBest"
                :motion-duration="$feedbackMotionDuration"
                :reduced-motion="$reducedMotion"
            />
        </native:column>
    @else
        <native:stack class="flex-1 w-full">
            <native:column class="h-full w-full safe-area">
                <native:column class="w-full px-4 pt-3 gap-3">
                    <x-native.games.shared.game-hud
                        :lives="$lives"
                        :max-lives="$maxLives"
                        :score="$score"
                        :combo="$combo"
                        :round="$waveIndex + 1"
                        :total="$totalWaves"
                        :motion-duration="$feedbackMotionDuration"
                    />

                    {{-- The standing order is the only thing the player must
                         hold in mind, so it stays put and stays legible. --}}
                    <native:column
                        native:key="barrage-order-{{ $waveIndex }}"
                        class="w-full items-center rounded-2xl px-4 py-2.5 border bg-theme-accent/15 border-theme-accent/40"
                        :animate-duration="$motionDuration"
                        animate-easing="ease-out"
                        a11y-label="Standing order: {{ $order }}"
                    >
                        <native:text class="text-[14] font-bold uppercase tracking-widest text-theme-accent">
                            {{ $order }}
                        </native:text>
                    </native:column>
                </native:column>

                {{-- The battle happens inside a dark viewport rather than on the
                     app canvas. A space game on a white field reads wrong and
                     the starfield is invisible against it — but making the whole
                     SCREEN dark would strand the shared HUD, which is built on
                     theme tokens and would go dark-on-dark in light mode.
                     Containing the darkness to a panel fixes both: the field
                     reads as space, the chrome stays themed. --}}
                <native:column class="flex-1 w-full px-4 pb-3">
                    <native:stack class="flex-1 w-full rounded-3xl bg-linear-to-b from-slate-900 via-slate-950 to-slate-900">
                        <x-native.games.vertex.starfield :reduced-motion="$reducedMotion" />

                        <native:column class="h-full w-full justify-start pt-4">
                            @if ($live)
                                <x-native.games.vertex.formation
                                    :invaders="$invaders"
                                    :wave-index="$waveIndex"
                                    :descent-ms="$descentMs"
                                    :last-struck="$lastStruck"
                                    :reduced-motion="$reducedMotion"
                                    :motion-duration="$feedbackMotionDuration"
                                />
                            @endif
                        </native:column>
                    </native:stack>
                </native:column>

                <native:column class="w-full px-4 pb-6 gap-2">
                    <native:text
                        native:key="barrage-callout-{{ $feedbackSerial }}"
                        class="text-[12] font-semibold uppercase tracking-widest text-center {{ $calloutClass }}"
                        :animate-duration="$feedbackMotionDuration"
                        animate-easing="ease-out"
                    >
                        {{ $callout }}
                    </native:text>

                    <x-native.games.shared.timer-bar
                        :seconds-per-round="100"
                        :seconds-remaining="(int) round($descentFraction * 100)"
                    />
                </native:column>
            </native:column>

            @if ($feedbackTone === 'swept')
                <x-native.games.shared.confetti-burst
                    :serial="$feedbackSerial"
                    :reduced-motion="$reducedMotion"
                    :accent="$accentColor"
                />
            @endif
        </native:stack>
    @endif
</native:column>
