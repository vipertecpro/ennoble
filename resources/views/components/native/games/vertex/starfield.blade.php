{{--
    The depth bed behind the formation: stars streaming down toward the camera.

    PARALLAX IS THE WHOLE TRICK. Two layers fall at different rates and sizes,
    and that rate difference is what the eye reads as depth — one layer at any
    speed just looks like drifting dots.

    Horizontal spread comes from LANES, not coordinates. `native:circle` takes
    absolute left/top but only inside a canvas, and it is not worth betting the
    backdrop on whether transform props survive that path; a row of `flex-1`
    lanes spreads the stars just as well using layout primitives that are known
    to work, and each lane's own delay breaks up the rhythm.

    It runs ENTIRELY on `animate-loop`, so it never calls back into PHP and the
    game's 250ms tick can never stutter it. Nothing here is interactive.
--}}
@props([
    'reducedMotion' => false,
])

@php
    $layers = $reducedMotion
        ? [['lanes' => 9, 'size' => 2, 'ms' => 6000, 'travel' => 560, 'opacity' => 0.35]]
        : [
            ['lanes' => 13, 'size' => 2, 'ms' => 5400, 'travel' => 620, 'opacity' => 0.35],
            ['lanes' => 8, 'size' => 3, 'ms' => 3200, 'travel' => 640, 'opacity' => 0.55],
            ['lanes' => 5, 'size' => 4, 'ms' => 2100, 'travel' => 660, 'opacity' => 0.80],
        ];
@endphp

<native:stack class="flex-1 w-full">
    @foreach ($layers as $depth => $layer)
        <native:row class="h-full w-full items-start">
            @for ($lane = 0; $lane < $layer['lanes']; $lane++)
                @php
                    // Deterministic offsets: a hash keeps each lane's phase
                    // stable between renders, where rand() would make the whole
                    // field jump on every tick.
                    $seed = crc32('barrage:'.$depth.':'.$lane);
                    $delay = $seed % $layer['ms'];
                @endphp
                <native:column class="flex-1 h-full items-center">
                    <native:column
                        class="w-[{{ $layer['size'] }}px] h-[{{ $layer['size'] }}px] rounded-full bg-white"
                        :opacity="$layer['opacity']"
                        :translate-y="$layer['travel']"
                        :animate-duration="$layer['ms']"
                        :animate-delay="$delay"
                        animate-easing="linear"
                        animate-loop
                    />
                </native:column>
            @endfor
        </native:row>
    @endforeach
</native:stack>
