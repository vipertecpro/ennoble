@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Tokens\Gradients')

@php
    // Only what is worth celebrating. A placement that buried a cell still
    // scores as it should and still shows in the end-of-run accuracy — but
    // saying so mid-run, every time, is nagging rather than information.
    $callout = match ($feedbackTone) {
        'quad' => 'Four at once',
        'clear' => 'Line cleared',
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

            {{-- The rail OVERLAYS the board rather than sitting beside it in
                 a row. A flex-1 viewport inside a column inside a row resolves
                 to nothing on iOS — the board vanished outright — which is the
                 same unbounded-proposal trap as everywhere else. A stack
                 overlays its children, so the board keeps the full width it
                 already sized correctly and the rail floats over the empty top
                 of the playfield, which is dead space until the stack grows. --}}
            {{-- Board on the left, rail on the right. Sizes are EXPLICIT
                 rather than flex-1 inside flex-1: a viewport given a flex
                 height inside a flex column inside a row resolved to nothing
                 on iOS and the board vanished, twice. h-full resolves. --}}
            <native:row class="flex-1 w-full gap-2">
                <native:column class="flex-1 h-full">
                    <native:scene-3d class="h-full w-full rounded-2xl" :scene="$scene" />
                </native:column>

                <native:column class="w-20 h-full gap-2">
                    {{-- HOLD parks the current piece and gives back whatever
                         was parked — stash the awkward one, keep building,
                         take it back when it fits. One swap per piece, or it
                         is just a free reroll. --}}
                    <native:pressable
                        @press="hold"
                        a11y-label="Hold this piece"
                        a11y-hint="Parks the current piece to use later; one swap per piece"
                        :press-scale="0.96"
                        class="w-20 items-center gap-1 rounded-xl px-2 py-2 bg-theme-surface border {{ Gradients::hairline() }}"
                    >
                        <native:text class="text-[8] font-semibold uppercase tracking-widest {{ $holdLocked ? 'text-theme-muted-text' : 'text-theme-accent' }}">Hold</native:text>
                        <native:column class="h-6 items-center justify-center">
                            <x-native.games.stack.piece-preview :piece="$holdPiece" :cell="7" />
                        </native:column>
                    </native:pressable>

                    <native:column class="w-20 items-center gap-2 rounded-xl px-2 py-2 bg-theme-surface border {{ Gradients::hairline() }}">
                        <native:text class="text-[8] font-semibold uppercase tracking-widest text-theme-muted-text">Next</native:text>
                        @foreach ($nextPieces as $upcoming)
                            <native:column class="h-6 items-center justify-center">
                                <x-native.games.stack.piece-preview :piece="$upcoming" :cell="7" />
                            </native:column>
                        @endforeach
                    </native:column>

                    {{-- Score sits directly under the queue: the two things a
                         player glances at are then in one place, instead of
                         one being at the far bottom of the screen. --}}
                    <native:column class="w-20 items-center gap-1 rounded-xl px-2 py-2 bg-theme-surface border {{ Gradients::hairline() }}">
                        <native:text class="text-[8] font-semibold uppercase tracking-widest text-theme-muted-text">Score</native:text>
                        <native:text
                            native:key="stack-score"
                            class="text-[17] font-bold text-theme-primary-text"
                            content-transition="numeric"
                            :animate-duration="$feedbackMotionDuration"
                            animate-easing="spring"
                        >{{ number_format($score) }}</native:text>

                        {{-- One text node, not a row of four. A row nested in
                             this column collapses to nothing on iOS, which is
                             how the level and line counts disappeared here the
                             first time. --}}
                        <native:text native:key="stack-progress" class="text-[10] text-theme-muted-text" content-transition="numeric">
                            Lv {{ $level }} · {{ $lines }} lines
                        </native:text>
                    </native:column>
                </native:column>
            </native:row>



            @if ($callout !== null)
                <native:text
                    native:key="stack-callout-{{ $feedbackSerial }}"
                    class="text-[11] font-semibold uppercase tracking-widest text-center text-theme-accent"
                    :animate-duration="$feedbackMotionDuration"
                    animate-easing="spring"
                >
                    {{ $callout }}
                </native:text>
            @endif

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
