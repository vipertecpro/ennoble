{{--
    The Vertex tunnel — the depth cue the whole game is staged inside.

    A stack of concentric rings, each looping scale 1 -> far while fading out,
    every ring offset by a slice of the cycle so one is always emerging as
    another dissolves. `ease-in` is what sells it: a ring creeps while it reads
    as distant, then rushes as it passes the camera, which is how real
    perspective behaves.

    This runs ENTIRELY on the native thread — `animate-loop` never calls back
    into PHP, so the tunnel keeps its framerate no matter what the game loop is
    doing. Nothing here is interactive; it is a backdrop.
--}}
@props([
    'reducedMotion' => false,
])

@php
    $ringCount = $reducedMotion ? 3 : 6;
    $cycleMs = 2600;
    $stagger = intdiv($cycleMs, $ringCount);
@endphp

<native:stack class="flex-1 w-full">
    @for ($ring = 0; $ring < $ringCount; $ring++)
        <native:column class="h-full w-full items-center justify-center">
            <native:column
                class="w-20 h-20 rounded-full border-2 border-theme-accent/40"
                :scale="$reducedMotion ? 4 : 9"
                :opacity="0.0"
                :animate-duration="$cycleMs"
                :animate-delay="$ring * $stagger"
                animate-easing="ease-in"
                animate-loop
            />
        </native:column>
    @endfor
</native:stack>
