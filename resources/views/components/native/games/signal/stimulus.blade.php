@use('App\Domain\Games\Signal\SignalPalette')

{{--
    Signal's stimulus: the rule banner over the word itself.

    The banner is the whole game — it says which half of the conflict counts
    this round, and it flashes on a switch so a flip is impossible to miss. The
    word below is painted in its ink; the ink hex is genuine data-driven color
    and comes from SignalPalette, the one PHP home for the game's vocabulary.
--}}
@props([
    'rule' => 'ink',
    'word',
    'ink',
    'ruleSwitched' => false,
    'roundIndex' => 0,
    'feedbackTone' => 'idle',
    'reducedMotion' => false,
    'motionDuration' => 0,
])

@php
    $inkHex = SignalPalette::hex($ink);
    $resolved = $feedbackTone !== 'idle';

    $banner = $rule === 'ink' ? 'Name the INK' : 'Name the WORD';
    $bannerHint = $rule === 'ink'
        ? 'the color you see'
        : 'the color you read';
@endphp

<native:column class="w-full items-center gap-5">
    <native:column
        native:key="signal-rule-{{ $rule }}-{{ $roundIndex }}"
        class="items-center gap-1 rounded-2xl px-5 py-2.5 border {{ $ruleSwitched ? 'bg-theme-accent border-theme-accent' : 'bg-theme-accent/15 border-theme-accent/40' }}"
        :scale="$reducedMotion || ! $ruleSwitched ? 1 : 1.06"
        :animate-duration="$motionDuration"
        animate-easing="ease-out"
        a11y-label="Rule: {{ $banner }} — {{ $bannerHint }}{{ $ruleSwitched ? '. The rule just changed.' : '' }}"
    >
        <native:text class="text-[13] font-bold uppercase tracking-widest {{ $ruleSwitched ? 'text-theme-on-accent' : 'text-theme-accent' }}">
            {{ $banner }}
        </native:text>
        <native:text class="text-[10] uppercase tracking-wider {{ $ruleSwitched ? 'text-theme-on-accent' : 'text-theme-muted-text' }}">
            {{ $ruleSwitched ? 'Rule changed · '.$bannerHint : $bannerHint }}
        </native:text>
    </native:column>

    <native:text
        native:key="signal-word-{{ $roundIndex }}"
        class="w-full text-[52] font-bold uppercase tracking-tight leading-tight text-center text-[{{ $inkHex }}]"
        :translate-y="$reducedMotion ? 0 : 8"
        :opacity="$resolved ? 0.65 : 1.0"
        :animate-duration="$motionDuration"
        animate-easing="ease-out"
        a11y-label="The word {{ SignalPalette::label($word) }}, printed in {{ SignalPalette::label($ink) }} ink"
    >
        {{ SignalPalette::label($word) }}
    </native:text>
</native:column>
