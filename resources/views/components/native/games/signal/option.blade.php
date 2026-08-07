@use('App\Domain\Games\Signal\SignalPalette')

{{--
    Signal answer tile — a color name, deliberately in neutral ink so the tile
    itself never leaks the answer. Before a call it is a plain theme surface;
    on resolution the correct tile ignites in the color it names (so the round
    teaches as it scores), the chosen wrong tile collapses to danger, and the
    untouched tiles dim back.
--}}
@props([
    'option',
    'answer',
    'selected' => null,
    'tone' => 'idle',
    'serial' => 0,
    'reducedMotion' => false,
    'motionDuration' => 0,
])

@php
    $resolved = $tone !== 'idle';
    $isAnswer = $option === $answer;
    $isSelected = $selected !== null && $option === $selected;
    $label = SignalPalette::label($option);

    // A border is always present (only its color changes) so switching states
    // never resizes the tile.
    $surface = 'bg-theme-surface shadow-md';
    $border = 'border border-theme-accent/20';
    $ink = 'text-theme-primary-text';

    if ($resolved && $isAnswer) {
        $hex = SignalPalette::hex($option);
        $surface = 'bg-['.$hex.'] shadow-lg';
        $border = 'border border-['.$hex.']';
        $ink = 'text-black';
    } elseif ($resolved && $isSelected) {
        $surface = 'bg-linear-to-b from-red-600/25 to-red-600/10 shadow-md';
        $border = 'border border-red-500/60';
        $ink = 'text-theme-danger';
    } elseif ($resolved) {
        $ink = 'text-theme-muted-text';
    }

    $scale = ($resolved && $isAnswer && ! $reducedMotion) ? 1.04 : 1.0;
    $opacity = ($resolved && ! $isAnswer && ! $isSelected) ? 0.5 : 1.0;
@endphp

<native:pressable
    native:key="signal-option-{{ $option }}-{{ $serial }}"
    class="flex-1 items-center justify-center rounded-2xl px-3 py-4 {{ $surface }} {{ $border }}"
    :press-scale="$reducedMotion ? 1 : 0.98"
    :scale="$scale"
    :opacity="$opacity"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $label }}"
    @press="chooseOption('{{ $option }}')"
>
    <native:text class="text-[16] font-semibold {{ $ink }}">{{ $label }}</native:text>
</native:pressable>
