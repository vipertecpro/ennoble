@use('App\Icons\Ios')
@use('App\Icons\Android')

@props([
    'phase' => 'ready',
    'currentDirection' => '',
    'feedbackTone' => 'idle',
    'feedbackSerial' => 0,
    'roundIndex' => 0,
    'windowMs' => 2000,
    'reducedMotion' => false,
    'motionDuration' => 0,
])

{{-- The Flow lane. A current surges in from the top carrying a direction arrow
     and travels toward the player's light as its window runs down; the player
     swipes to meet it. The arrow ring and orb recolour on the resolution beat.
     Colours come from theme tokens, so the game's scoped indigo accent flows
     through automatically. --}}
@php
    $live = ($phase === 'flow');

    [$ios, $android, $directionLabel] = match ($currentDirection) {
        'left' => [Ios::ArrowLeft, Android::ArrowLeft, 'left'],
        'right' => [Ios::ArrowRight, Android::ArrowRight, 'right'],
        'up' => [Ios::ArrowUp, Android::ArrowUpward, 'up'],
        'down' => [Ios::ArrowDown, Android::ArrowDownward, 'down'],
        default => [Ios::ArrowRight, Android::ArrowRight, ''],
    };

    $ringBg = match ($feedbackTone) {
        'wrong', 'timeout' => 'bg-linear-to-br from-red-500 to-red-600 border border-red-400/70',
        default => 'bg-linear-to-br from-indigo-400 to-cyan-400 border border-cyan-300/60',
    };

    $label = match (true) {
        $phase === 'ready' => 'Get ready…',
        $feedbackTone === 'correct' => 'Nice!',
        $feedbackTone === 'wrong' => 'Wrong way',
        $feedbackTone === 'timeout' => 'Missed it',
        default => 'Swipe '.$directionLabel,
    };

    $labelColor = in_array($feedbackTone, ['wrong', 'timeout'], true) ? 'text-theme-danger' : 'text-theme-accent';

    // How far the current travels down the lane over its window.
    $travel = $reducedMotion ? 40 : 200;

    // The orb celebrates a match, dims on a miss, and idles otherwise.
    $orbScale = match ($feedbackTone) {
        'correct' => 1.18,
        'wrong', 'timeout' => 0.9,
        default => 1.0,
    };
@endphp

<native:column class="flex-1 w-full items-center justify-between py-6">
    {{-- Incoming current --}}
    <native:column class="h-28 items-center justify-start">
        @if ($live)
            <native:column
                native:key="current-{{ $roundIndex }}"
                class="w-20 h-20 items-center justify-center rounded-full shadow-lg {{ $ringBg }}"
                :translate-y="$travel"
                :animate-duration="$reducedMotion ? 0 : $windowMs"
                animate-easing="linear"
                a11y-label="Current flowing {{ $directionLabel }}"
            >
                <x-native.ui.icon :ios="$ios" :android="$android" :size="34" color="#FFFFFF" />
            </native:column>
        @endif
    </native:column>

    {{-- Prompt --}}
    <native:text
        native:key="flow-label-{{ $feedbackTone }}-{{ $feedbackSerial }}"
        class="text-[15] font-bold uppercase tracking-widest {{ $labelColor }}"
        :scale="$reducedMotion ? 1 : 1.04"
        :animate-duration="$motionDuration"
        animate-easing="ease-out"
    >
        {{ $label }}
    </native:text>

    {{-- The player's light --}}
    <native:column class="items-center justify-center">
        <native:column
            native:key="orb-{{ $feedbackSerial }}-{{ $feedbackTone }}"
            class="w-24 h-24 items-center justify-center rounded-full bg-cyan-400/15 border border-cyan-400/30 shadow-lg"
            :scale="$reducedMotion ? 1 : $orbScale"
            :animate-duration="$motionDuration"
            animate-easing="ease-out"
            a11y-label="Your light"
        >
            <native:column class="w-14 h-14 rounded-full bg-linear-to-br from-cyan-300 to-indigo-500 border border-cyan-200/60 shadow-lg" />
        </native:column>
    </native:column>
</native:column>
