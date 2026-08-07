@use('App\Domain\Games\Vertex\VertexScoringService')
@use('App\NativeUI\Tokens\Gradients')

@php
    $live = $phase === 'flight';

    // The strike ring is DERIVED from the scoring constant, not eyeballed: an
    // object mounts at $baseSize and tweens linearly to $maxScale over its
    // flight, so the depth that pays full bonus lands at exactly this diameter.
    // Aiming at the ring and maximising the bonus are therefore the same act,
    // and the two can never drift apart.
    $baseSize = 48;
    $maxScale = 5.5;
    $ringSize = (int) round($baseSize * (1 + ($maxScale - 1) * VertexScoringService::SWEET_SPOT));

    [$callout, $calloutTone] = match ($feedbackTone) {
        'struck' => [$lastDepthBonus >= 50 ? 'Perfect strike +'.$lastDepthBonus : 'Struck +'.$lastDepthBonus, 'good'],
        'passed' => ['Held — decoy passed', 'good'],
        'false-alarm' => ['False strike', 'bad'],
        'missed' => ['Target got through', 'bad'],
        default => ['Round '.($roundIndex + 1).' of '.$totalRounds, 'idle'],
    };

    $calloutClass = match ($calloutTone) {
        'good' => 'text-theme-accent',
        'bad' => 'text-theme-danger',
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
            {{-- Depth, back to front: tunnel, aim ring, object, then the tap
                 surface with the HUD drawn on it. --}}
            <x-native.games.vertex.tunnel :reduced-motion="$reducedMotion" />

            <native:column class="h-full w-full items-center justify-center">
                <native:column
                    class="w-[{{ $ringSize }}px] h-[{{ $ringSize }}px] rounded-full border-2 border-dashed border-theme-accent/35"
                    a11y-label="Strike ring"
                />
            </native:column>

            @if ($live)
                <x-native.games.vertex.projectile
                    :shape="$objectShape"
                    :flight-ms="$flightMs"
                    :round-index="$roundIndex"
                    :base-size="$baseSize"
                    :max-scale="$maxScale"
                    :reduced-motion="$reducedMotion"
                />
            @endif

            {{-- The whole play area strikes. The object is rushing at the
                 camera, so requiring a hit on its exact bounds would test
                 dexterity instead of inhibition. --}}
            <native:pressable
                class="h-full w-full"
                :press-scale="1"
                a11y-label="Strike"
                a11y-hint="Tap anywhere to strike the incoming object"
                @press="strike"
            >
                <native:column class="h-full w-full safe-area">
                    <native:column class="w-full px-4 pt-3 gap-3">
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

                    <native:spacer />

                    <native:column class="w-full px-4 pb-8 items-center gap-3">
                        <native:text
                            native:key="vertex-callout-{{ $feedbackSerial }}"
                            class="text-[12] font-semibold uppercase tracking-widest {{ $calloutClass }}"
                            :animate-duration="$feedbackMotionDuration"
                            animate-easing="ease-out"
                        >
                            {{ $phase === 'ready' ? 'Get ready…' : $callout }}
                        </native:text>

                        <x-native.games.vertex.target-badge
                            :shape="$targetShape"
                            :switched="$targetSwitched"
                            :round-index="$roundIndex"
                            :reduced-motion="$reducedMotion"
                            :motion-duration="$motionDuration"
                        />
                    </native:column>
                </native:column>
            </native:pressable>

            @if ($feedbackTone === 'struck')
                <x-native.games.shared.confetti-burst
                    :serial="$feedbackSerial"
                    :reduced-motion="$reducedMotion"
                    :accent="$accentColor"
                />
            @endif
        </native:stack>
    @endif
</native:column>
