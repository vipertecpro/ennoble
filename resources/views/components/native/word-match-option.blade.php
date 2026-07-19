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
    Word Match answer tile. Before an answer, a neutral surface tile. On
    resolution the correct tile morphs to the lime accent (the screen's one
    lime moment) while the chosen wrong tile collapses to a danger outline and
    shakes; untouched tiles dim back. Mirrors Elevate's correct-morph /
    wrong-collapse feedback.
--}}
@php
    $answered = $tone !== 'idle';
    $isAnswer = $option === $answer;
    $isSelected = $selected !== null && $option === $selected;

    $surface = 'bg-theme-surface shadow-sm';
    $ink = 'text-theme-primary-text';

    if ($answered && $isAnswer) {
        $surface = 'bg-theme-accent';
        $ink = 'text-theme-on-accent';
    } elseif ($answered && $isSelected) {
        $surface = 'bg-theme-secondary-surface border border-theme-danger';
        $ink = 'text-theme-danger';
    } elseif ($answered) {
        $ink = 'text-theme-muted-text';
    }

    $shake = ($answered && $isSelected && ! $isAnswer && ! $reducedMotion) ? ($serial % 2 === 0 ? -7 : 7) : 0;
    $scale = ($answered && $isAnswer && ! $reducedMotion) ? 1.04 : 1.0;
    $opacity = ($answered && ! $isAnswer && ! $isSelected) ? 0.5 : 1.0;
@endphp

<native:pressable
    native:key="word-option-{{ $option }}-{{ $serial }}"
    class="w-full items-center justify-center rounded-2xl px-4 py-4 {{ $surface }}"
    :press-scale="$reducedMotion ? 1 : 0.98"
    :translate-x="$shake"
    :scale="$scale"
    :opacity="$opacity"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $option }}"
    @press="chooseOption('{{ $option }}')"
>
    <native:text class="text-[16] font-semibold {{ $ink }}">{{ $option }}</native:text>
</native:pressable>
