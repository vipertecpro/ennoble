@use('App\Icons\AndroidOutlined')
@use('App\Icons\Ios')
@use('App\NativeUI\Home\GamingQuotes')
@use('App\NativeUI\Tokens\Gradients')

{{--
    One horizontal strip of quotes, translated left a tick at a time, framed by
    a pair of gradient rules.

    THE RULES FADE AT BOTH ENDS on purpose. A full-width saturated bar reads as
    a warning banner, and repeating the CTA gradient here made the strip compete
    with the Play button directly below it. `from-transparent via-… to-transparent`
    at 1pt gives the same gradient edge as a quiet frame instead of a stripe.
    Gradient stops must be palette names — a border can only take one flat
    colour, which is why these are drawn as bars rather than a `border-y`.

    THE SCROLL-VIEW IS LOAD-BEARING, not decoration. The strip is thousands of
    points wide, and nothing else here clips: `overflow-hidden` is silently
    dropped by the class parser, and an unclipped oversized row propagates its
    intrinsic width up the tree and stretches the whole Home layout off-screen
    (it rendered Home blank). `<native:scroll-view horizontal>` is the only
    container that sets `overflow: 2`, which both clips the strip and stops flex
    from being constrained by its content. Do not remove it.

    Every slot is given an EXPLICIT width from GamingQuotes rather than being
    left to size itself — that is what lets PHP know the strip's total length
    and wrap on it exactly. Never replace these with intrinsic-width slots; the
    scroll offset would immediately drift out of step with the render. The
    `max-lines="1"` pins are the safety net for that estimate: a quote that
    overruns its slot truncates rather than wrapping and doubling the height.

    THERE IS NO `native:poll` HERE, deliberately. The scroll is one declarative
    `animate-loop`, so the ticker never needs a render of its own — and because
    its props are identical on every frame, Home's own once-a-second clock
    re-render diffs to nothing and leaves the running animation untouched. That
    immunity is the entire point: driving the transform from PHP scrolled at the
    right average speed but juddered, because every neighbouring re-render
    landed mid-tween.

    The two nested rows are load-bearing. The inner one owns the loop and always
    runs 0 -> -stripWidth; the outer one carries a STATIC offset so a resumed
    segment can line rotated content up with a loop that can only start from
    zero. Collapsing them into one row makes pause-and-resume jump.
--}}
@php
    $rule = 'w-full h-[1px] bg-linear-to-r from-transparent via-rose-500/50 to-transparent';
    $size = GamingQuotes::FONT_SIZE;
    $separator = GamingQuotes::SEPARATOR_CELL;
@endphp

<native:column class="w-full bg-theme-surface-elevated">
    <native:column class="{{ $rule }}" />

    <native:scroll-view horizontal :shows-indicators="false" class="w-full">
        <native:pressable
            class="py-3"
            :press-scale="1"
            a11y-label="Gaming quotes"
            a11y-hint="Press and hold to stop the quotes scrolling"
            @tapDown="hold"
            @tapUp="release"
        >
            <native:row class="items-center" :translate-x="$baseOffset">
            <native:row
                native:key="quote-loop-{{ $segmentKey }}"
                class="items-center"
                :translate-x="$paused ? 0 : $loopDistance"
                :animate-duration="$paused ? 0 : $loopMs"
                :animate-loop="! $paused"
                animate-easing="linear"
            >
                @foreach ($slots as $index => $slot)
                    <native:row
                        class="w-[{{ $slot['width'] }}px] items-center gap-2"
                        native:key="quote-{{ $index }}"
                    >
                        {{-- The separator OPENS each slot in a fixed-width cell
                             (GamingQuotes::SEPARATOR_CELL). Leading, because a
                             slot's spare width collects at its END — a trailing
                             diamond drifts with that slack and overlaps the next
                             quote whenever the width estimate runs short. The
                             glow is a stack: a soft oversized halo behind a
                             small solid diamond. --}}
                        <native:stack class="w-[{{ $separator }}px] items-center justify-center">
                            <native:column class="w-full items-center justify-center">
                                <x-native.ui.icon
                                    :ios="Ios::DiamondFill"
                                    :android="AndroidOutlined::Diamond"
                                    :size="24"
                                    color="#B8860B1F"
                                    dark-color="#FFD24A24"
                                />
                            </native:column>
                            <native:column class="w-full items-center justify-center">
                                <x-native.ui.icon
                                    :ios="Ios::DiamondFill"
                                    :android="AndroidOutlined::Diamond"
                                    :size="17"
                                    {{-- The halo's softness rides in the colour's
                                         ALPHA (#RRGGBBAA), which is the only way
                                         to fade an icon — opacity-* is a class,
                                         and classes here only reach the element,
                                         not the glyph's tint. --}}
                                    color="#B8860B33"
                                    dark-color="#FFD24A38"
                                />
                            </native:column>
                            <native:column class="w-full items-center justify-center">
                                <x-native.ui.icon
                                    :ios="Ios::DiamondFill"
                                    :android="AndroidOutlined::Diamond"
                                    :size="10"
                                    color="#C8940A"
                                    dark-color="#FFCE45"
                                />
                            </native:column>
                        </native:stack>

                        <native:text
                            font="quote"
                            max-lines="1"
                            class="text-[{{ $size }}] text-theme-primary-text"
                        >
                            {{ $slot['text'] }}
                        </native:text>
                        <native:text
                            font="quote-source"
                            max-lines="1"
                            class="text-[{{ $size }}] text-theme-muted-text"
                        >
                            {{ $slot['source'] }}
                        </native:text>

                    </native:row>
                @endforeach
            </native:row>
            </native:row>
        </native:pressable>
    </native:scroll-view>

    <native:column class="{{ $rule }}" />
</native:column>
