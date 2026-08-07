@use('App\Domain\Games\Vertex\VertexVocabulary')

{{--
    One invader in the formation. Shape and colour are INDEPENDENT and both
    carry meaning — under "fire at blue rings" neither attribute alone
    separates the field — so the silhouette must never be inferable from the
    colour, and vice versa.

    The whole cell is the tap target rather than the glyph itself: the invader
    is small and descending, and demanding a hit on its exact bounds would test
    dexterity instead of search.
--}}
@props([
    'invader',
    'struck' => false,
    'reducedMotion' => false,
    'motionDuration' => 0,
])

@php
    $hex = VertexVocabulary::colourHex($invader['colour']);

    $form = match ($invader['shape']) {
        'block' => 'w-9 h-9 rounded-md bg-['.$hex.']',
        'bar' => 'w-11 h-4 rounded-full bg-['.$hex.']',
        'ring' => 'w-9 h-9 rounded-full border-[5px] border-['.$hex.']',
        default => 'w-9 h-9 rounded-full bg-['.$hex.']',
    };

    $label = VertexVocabulary::colourLabel($invader['colour']).' '.VertexVocabulary::shapeLabel($invader['shape']);
@endphp

<native:pressable
    class="flex-1 items-center justify-center py-2"
    :press-scale="$reducedMotion ? 1 : 0.9"
    a11y-label="{{ $label }}"
    a11y-hint="Fire if it matches the standing order"
    @press="fire('{{ $invader['id'] }}')"
>
    <native:column
        class="{{ $form }} shadow-md"
        :scale="$struck && ! $reducedMotion ? 1.25 : 1.0"
        :opacity="$struck ? 0.0 : 1.0"
        :animate-duration="$motionDuration"
        animate-easing="ease-out"
    />
</native:pressable>
