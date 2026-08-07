@use('App\Domain\Games\Vertex\VertexShapes')

{{--
    A single object flying out of the vanishing point.

    The motion is ONE native tween, not a PHP animation: the element mounts at
    its layout size and animates to `scale` over exactly the flight window, so
    the platform renders every frame while PHP only knows when the window opened
    and closed. `native:key` carries the round index, so each round mounts a
    fresh element and the tween restarts from the vanishing point.

    Easing is LINEAR on purpose. The tunnel behind it supplies the perspective
    feel, while the object's depth must stay a straight function of elapsed time
    — the strike bonus is scored from the clock, and an eased flight would make
    the ring the player aims at disagree with the maths that pays them.
--}}
@props([
    'shape',
    'flightMs' => 2100,
    'roundIndex' => 0,
    'baseSize' => 48,
    'maxScale' => 5.5,
    'reducedMotion' => false,
])

@php
    $hex = VertexShapes::hex($shape);

    // Silhouette AND colour both identify the form, so it stays readable at any
    // depth and never depends on colour alone.
    $form = match ($shape) {
        'block' => 'w-12 h-12 rounded-xl bg-['.$hex.']',
        'bar' => 'w-16 h-5 rounded-full bg-['.$hex.']',
        'ring' => 'w-12 h-12 rounded-full border-[6px] border-['.$hex.']',
        default => 'w-12 h-12 rounded-full bg-['.$hex.']',
    };

    // A slow tumble reads as a solid passing through space rather than a
    // sticker being enlarged.
    $spin = $reducedMotion ? 0 : 150;
@endphp

<native:column class="h-full w-full items-center justify-center">
    <native:column
        native:key="vertex-object-{{ $roundIndex }}"
        class="{{ $form }} shadow-lg"
        :scale="$reducedMotion ? 2.5 : $maxScale"
        :rotate="$spin"
        :animate-duration="$flightMs"
        animate-easing="linear"
        a11y-label="{{ VertexShapes::label($shape) }} incoming"
    />
</native:column>
