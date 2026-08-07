@use('App\Domain\Games\Vertex\VertexShapes')

{{--
    The standing order: which form to strike. This is the only thing on screen
    the player must hold in mind, so it stays put and flashes solid on the round
    where the target re-keys — a silent re-key is unfair, a loud one is the
    whole point.

    The swatch mirrors the projectile's silhouette and colour exactly, so the
    prompt and the object in flight are visibly the same vocabulary.
--}}
@props([
    'shape',
    'switched' => false,
    'roundIndex' => 0,
    'reducedMotion' => false,
    'motionDuration' => 0,
])

@php
    $hex = VertexShapes::hex($shape);

    $swatch = match ($shape) {
        'block' => 'w-5 h-5 rounded-md bg-['.$hex.']',
        'bar' => 'w-7 h-2.5 rounded-full bg-['.$hex.']',
        'ring' => 'w-5 h-5 rounded-full border-[3px] border-['.$hex.']',
        default => 'w-5 h-5 rounded-full bg-['.$hex.']',
    };
@endphp

<native:column
    native:key="vertex-target-{{ $shape }}-{{ $roundIndex }}"
    class="items-center gap-2 rounded-3xl px-5 pt-3 pb-4 border {{ $switched ? 'bg-theme-accent border-theme-accent' : 'bg-theme-accent/15 border-theme-accent/40' }}"
    :scale="$reducedMotion || ! $switched ? 1 : 1.06"
    :animate-duration="$motionDuration"
    animate-easing="ease-out"
    a11y-label="Strike the {{ VertexShapes::label($shape) }}{{ $switched ? '. New target.' : '' }}"
>
    <native:row class="items-center gap-2">
        <native:column class="{{ $swatch }}" />
        <native:text class="text-[13] font-bold uppercase tracking-widest {{ $switched ? 'text-theme-on-accent' : 'text-theme-accent' }}">
            Strike the {{ VertexShapes::label($shape) }}
        </native:text>
    </native:row>
    <native:text class="text-[10] uppercase tracking-wider {{ $switched ? 'text-theme-on-accent' : 'text-theme-muted-text' }}">
        {{ $switched ? 'New target · let the rest pass' : 'Let the rest pass' }}
    </native:text>
</native:column>
