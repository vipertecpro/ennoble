@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\Gradients')

@php
    $callout = match ($feedbackTone) {
        'quad' => 'Four at once',
        'clear' => 'Line cleared',
        'buried' => 'Buried a cell',
        'topped' => 'Topped out',
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
        <native:column class="h-full w-full px-4 gap-2 safe-area">
            {{-- Run progress. There is no clock in this game, so the bar
                 measures pieces used rather than time — the one thing that
                 genuinely runs out. --}}
            <native:row class="w-full items-center gap-3 pt-2">
                <native:pressable
                    @press="exit"
                    a11y-label="Back to games"
                    a11y-hint="Leaves this game"
                    :press-scale="0.9"
                    class="w-9 h-9 items-center justify-center rounded-full bg-theme-surface-variant"
                >
                    <x-native.ui.icon :ios="Ios::ChevronLeft" :android="AndroidOutlined::ArrowBack" :size="16" />
                </native:pressable>

                <native:column class="flex-1">
                    {{-- The shared bar, fed pieces instead of seconds: there is
                         no clock here, and pieces are the thing that runs out. --}}
                    <x-native.games.shared.timer-bar
                        :seconds-per-round="max(1, $totalPieces)"
                        :seconds-remaining="max(0, $totalPieces - $pieceIndex)"
                    />
                </native:column>

                <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">
                    {{ $pieceIndex + 1 }}/{{ $totalPieces }}
                </native:text>
            </native:row>

            {{-- Hold and the queue, exactly the information a player needs to
                 plan more than one piece ahead. --}}
            <native:row class="w-full gap-2 items-stretch">
                <native:pressable
                    @press="hold"
                    a11y-label="Hold this piece"
                    a11y-hint="Sets the current piece aside until the next one"
                    :press-scale="0.97"
                    class="rounded-2xl px-3 py-2 bg-theme-surface border {{ Gradients::hairline() }}"
                >
                    <native:column class="gap-1 items-start">
                        <native:text class="text-[9] font-semibold uppercase tracking-widest {{ $holdLocked ? 'text-theme-muted-text' : 'text-theme-accent' }}">Hold</native:text>
                        <native:column class="h-8 justify-center">
                            <x-native.games.stack.piece-preview :piece="$holdPiece" />
                        </native:column>
                    </native:column>
                </native:pressable>

                <native:column class="flex-1 rounded-2xl px-3 py-2 bg-theme-surface border {{ Gradients::hairline() }}">
                    <native:column class="gap-1">
                        <native:text class="text-[9] font-semibold uppercase tracking-widest text-theme-muted-text">Next</native:text>
                        <native:row class="gap-4 h-8 items-center">
                            @foreach ($nextPieces as $upcoming)
                                <x-native.games.stack.piece-preview :piece="$upcoming" />
                            @endforeach
                        </native:row>
                    </native:column>
                </native:column>
            </native:row>

            <native:column class="flex-1 w-full">
                <native:scene-3d class="flex-1 w-full rounded-2xl" :scene="$scene" />
            </native:column>

            <native:row class="w-full items-center gap-3 rounded-2xl px-4 py-2 bg-theme-surface border {{ Gradients::hairline() }}">
                <native:column class="items-start">
                    <native:text class="text-[9] font-semibold uppercase tracking-widest text-theme-muted-text">Level</native:text>
                    <native:text class="text-[18] font-bold text-theme-primary-text">{{ $level }}</native:text>
                </native:column>

                <native:column class="items-start">
                    <native:text class="text-[9] font-semibold uppercase tracking-widest text-theme-muted-text">Lines</native:text>
                    <native:text class="text-[18] font-bold text-theme-primary-text">{{ $lines }}</native:text>
                </native:column>

                <native:spacer class="flex-1" />

                <native:column class="items-end">
                    <native:text class="text-[9] font-semibold uppercase tracking-widest text-theme-muted-text">Score</native:text>
                    <native:text
                        native:key="stack-score-{{ $score }}"
                        class="text-[22] font-bold text-theme-primary-text"
                        :scale="$feedbackMotionDuration > 0 ? 1.05 : 1"
                        :animate-duration="$feedbackMotionDuration"
                        animate-easing="ease-out"
                    >{{ number_format($score) }}</native:text>
                </native:column>
            </native:row>

            <native:text
                native:key="stack-callout-{{ $feedbackSerial }}"
                class="text-[11] font-semibold uppercase tracking-widest text-center {{ $calloutClass }}"
                :animate-duration="$feedbackMotionDuration"
                animate-easing="ease-out"
            >
                {{ $callout ?? ' ' }}
            </native:text>

            {{-- Rotate sits apart from the movement cluster, as it does on the
                 reference: it is a different KIND of action, and grouping it
                 with the arrows makes it easy to hit by mistake. --}}
            <native:row class="w-full items-center gap-2 pb-4">
                <x-native.games.stack.control action="rotate" label="Rotate" press="rotate" />
                <native:spacer class="flex-1" />
                <x-native.games.stack.control action="left" label="Move left" press="moveLeft" />
                <x-native.games.stack.control action="down" label="Soft drop" press="softDrop" />
                <x-native.games.stack.control action="right" label="Move right" press="moveRight" />
                <x-native.games.stack.control action="drop" label="Hard drop" press="hardDrop" :primary="true" />
            </native:row>
        </native:column>
    @endif
</native:column>
