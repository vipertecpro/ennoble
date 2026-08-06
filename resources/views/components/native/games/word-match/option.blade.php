@props([
    'option',
    'answer',
    'selected' => null,
    'tone' => 'idle',
    'serial' => 0,
    'reducedMotion' => false,
    'motionDuration' => 0,
])

{{--
    Word Match answer tile — a glowing gradient card. Before an answer, a
    subtle theme-surface gradient with a faint lime glow border. On resolution
    the correct tile ignites into a bright lime → cyan gradient (the screen's
    one big lime moment) while the chosen wrong tile collapses to a red danger
    gradient and shakes; untouched tiles dim back. Mirrors Elevate's
    correct-morph / wrong-collapse feedback.
--}}
@php
    $answered = $tone !== 'idle';
    $isAnswer = $option === $answer;
    $isSelected = $selected !== null && $option === $selected;

    // A border is always present (only its color changes) so switching states
    // never changes the tile's size (no content shift).
    $surface = 'bg-theme-surface shadow-md';
    $border = 'border border-lime-400/20';
    $ink = 'text-theme-primary-text';

    if ($answered && $isAnswer) {
        $surface = 'bg-linear-to-r from-lime-400 to-cyan-400 shadow-lg';
        $border = 'border border-lime-300/60';
        $ink = 'text-black';
    } elseif ($answered && $isSelected) {
        $surface = 'bg-linear-to-b from-red-600/25 to-red-600/10 shadow-md';
        $border = 'border border-red-500/60';
        $ink = 'text-theme-danger';
    } elseif ($answered) {
        $ink = 'text-theme-muted-text';
    }

    $scale = ($answered && $isAnswer && ! $reducedMotion) ? 1.04 : 1.0;
    $opacity = ($answered && ! $isAnswer && ! $isSelected) ? 0.5 : 1.0;
@endphp

<native:pressable
    native:key="word-option-{{ $option }}-{{ $serial }}"
    class="w-full items-center justify-center rounded-2xl px-4 py-4 {{ $surface }} {{ $border }}"
    :press-scale="$reducedMotion ? 1 : 0.98"
    :scale="$scale"
    :opacity="$opacity"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $option }}"
    @press="chooseOption('{{ $option }}')"
>
    <native:text class="text-[16] font-semibold {{ $ink }}">{{ $option }}</native:text>
</native:pressable>
