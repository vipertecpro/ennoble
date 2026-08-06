{{--
    Gradient card wrapper — the base surface of the game-premium direction.

    A subtle theme-surface gradient fill (surface -> surface-variant, both
    dark/light aware because they are theme tokens), rounded-3xl corners and
    shadow for depth. Pass an optional `accent` palette colour to add a glowing
    coloured border that gives the card identity (e.g. accent="orange-500",
    accent="lime-400", accent="red-600").

    Padding defaults to p-4; override with the `class` prop when a card needs a
    different rhythm.
--}}

@props([
    'accent' => null,
    'class' => 'p-4',
])

@php
    $border = $accent
        ? "border border-{$accent}/40"
        : 'border border-theme-border';
@endphp

<native:column class="w-full rounded-3xl bg-theme-surface shadow-lg {{ $border }} {{ $class }}">
    {{ $slot }}
</native:column>
