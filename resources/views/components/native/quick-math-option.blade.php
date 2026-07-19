@props([
    'option',
    'answer',
    'selected' => null,
    'tone' => 'idle',
    'serial' => 0,
    'reducedMotion' => false,
    'motionDuration' => 0,
])

{{-- Quick Math answer tile (numeric). Correct tile morphs to the lime accent;
     the chosen wrong tile collapses to a danger outline and shakes; untouched
     tiles dim. Sized flex-1 so four tiles form a 2×2 grid. --}}
@php
    $answered = $tone !== 'idle';
    $isAnswer = (int) $option === (int) $answer;
    $isSelected = $selected !== null && (int) $option === (int) $selected;

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
    native:key="math-option-{{ $option }}-{{ $serial }}"
    class="flex-1 items-center justify-center rounded-2xl px-4 py-5 {{ $surface }}"
    :press-scale="$reducedMotion ? 1 : 0.98"
    :translate-x="$shake"
    :scale="$scale"
    :opacity="$opacity"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="{{ $option }}"
    @press="chooseOption('{{ $option }}')"
>
    <native:text class="text-[20] font-bold {{ $ink }}">{{ number_format($option) }}</native:text>
</native:pressable>
