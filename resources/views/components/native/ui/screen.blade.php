{{--
    Screen BACKGROUND wrapper.

    A solid, mode-aware `bg-theme-background` fill on a full-height column — the
    same structure the screens have always used, so the scroll view inside gets
    a real full-height viewport.

    IMPORTANT: do NOT put a linear gradient on this h-full fill column. A
    gradient fill needs a concrete size, so on a flexible-height container it
    collapses the layout (the scroll viewport shrank to ~half the screen and
    clipped content). If we want gradient depth behind the content later, it
    must go in a background <native:stack> layer, never on the fill column.

    Merge any extra classes (e.g. safe-area-top) via the optional {{ $class }}.
--}}

@props([
    'class' => '',
])

<native:column class="h-full w-full bg-theme-background {{ $class }}">
    {{ $slot }}
</native:column>
