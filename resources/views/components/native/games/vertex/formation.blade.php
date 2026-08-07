{{--
    The descending formation.

    THE DESCENT IS ONE NATIVE TWEEN on this column: it mounts at the top and
    animates to `travel` over exactly the wave's descent window, so the platform
    renders every frame while PHP only knows when the window opened and closed.

    Destroying an invader removes a CHILD; this column's own key and transform
    props are untouched, so the diff never reaches the animation and the descent
    keeps running through every shot. That is the same rule the quote ticker
    depends on — never recompute an animated element's props mid-flight.

    Linear easing, because the descent clock is a straight countdown in PHP; an
    eased fall would drift out of step with the deadline the player is racing.
--}}
@props([
    'invaders' => [],
    'waveIndex' => 0,
    'descentMs' => 6000,
    'lastStruck' => -1,
    'columns' => 3,
    'reducedMotion' => false,
    'motionDuration' => 0,
])

@php
    $rows = array_chunk($invaders, max(1, (int) $columns));
    $travel = $reducedMotion ? 90 : 300;
@endphp

<native:column
    native:key="barrage-formation-{{ $waveIndex }}"
    class="w-full px-6 gap-3"
    :translate-y="$travel"
    :animate-duration="$descentMs"
    animate-easing="linear"
>
    @foreach ($rows as $row)
        <native:row class="w-full items-center gap-3">
            @foreach ($row as $invader)
                <x-native.games.vertex.invader
                    :invader="$invader"
                    :struck="(int) $invader['id'] === (int) $lastStruck"
                    :reduced-motion="$reducedMotion"
                    :motion-duration="$motionDuration"
                    :key="'invader-'.$waveIndex.'-'.$invader['id']"
                />
            @endforeach
        </native:row>
    @endforeach
</native:column>
