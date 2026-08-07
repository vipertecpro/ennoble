@use('Native\Mobile\UI\Theme')

{{--
    Home's masthead: greeting on the left, the clock stacked as a tall column on
    the right.

    THE CLOCK IS IN FLOW, NOT BEHIND. Every other watermark idea floats the time
    in a `stack` where the screen edge crops it; here the hour and minute are
    real layout, stacked one per line and set nearly transparent. That makes the
    time a structural column holding up the right side of the block rather than
    decoration behind the text — which is why it reads at this size without
    fighting the greeting.

    THE TWO COLUMNS ARE SIZED TO MATCH. The left stack (eyebrow + two greeting
    lines + level + rail) and the right stack (two numerals + meridiem) come out
    within a few points of each other, so `items-center` alone balances them.
    That is deliberate: pushing content to the bottom of a column does NOT work
    reliably here — a fixed-height column ignores `justify-*` on iOS and pins
    its children to one edge — so the layout is built to not need it.

    It is also NOT a card: no border, radius or shadow, sitting straight on the
    canvas so it reads as the page's masthead. That is why it renders outside
    the scroll column's `px-4` gutter and carries its own padding.
--}}
@props([
    'greeting',
    'displayName',
    'todayLabel',
    'hour',
    'minute',
    'meridiem',
    'currentStreak' => 0,
    'level' => 1,
    'levelTitle' => '',
    'levelProgress' => 0.0,
    'xpLabel' => '',
    'motionDuration' => 0,
])

@php
    $tokens = Theme::all();
    $railFill = data_get($tokens, 'light.accent', '#F43F5E');
@endphp

<native:row class="w-full items-center gap-4 px-4" :animate-duration="$motionDuration">
    <native:column class="flex-1 gap-1">
        <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">
            {{ $todayLabel }}
        </native:text>

        <native:text font="headline" class="text-[25] tracking-tight leading-tight text-theme-primary-text">
            {{ $greeting }},
        </native:text>
        <native:text font="headline" class="text-[25] tracking-tight leading-tight text-theme-accent">
            {{ $displayName }}
        </native:text>

        <native:text class="text-[12.5] text-theme-secondary-text">
            Level {{ $level }} · {{ $levelTitle }}
        </native:text>

        <native:progress-bar
            :value="max(0.0, min(1.0, (float) $levelProgress))"
            :color="$railFill"
            class="w-full h-[4px] rounded-full"
            a11y-label="Level {{ $level }} progress, {{ $xpLabel }}"
        />

        <native:text class="text-[11] text-theme-muted-text">
            {{ $xpLabel }} · {{ $currentStreak > 0 ? $currentStreak.'-day streak' : 'no streak yet' }}
        </native:text>
    </native:column>

    {{-- Set in the numeric face so the two lines are the same width whatever
         the digits are; a proportional face would make 08 and 14 disagree. --}}
    <native:column
        class="items-end"
        a11y-label="The time is {{ $hour }}:{{ $minute }} {{ $meridiem }}"
    >
        <native:text
            font="numeric"
            class="text-[62] leading-none tracking-tight text-theme-primary-text"
            :opacity="0.26"
        >
            {{ $hour }}
        </native:text>
        <native:text
            font="numeric"
            class="text-[62] leading-none tracking-tight text-theme-primary-text"
            :opacity="0.14"
        >
            {{ $minute }}
        </native:text>
        <native:text class="text-[11] font-semibold uppercase tracking-widest text-theme-muted-text">
            {{ $meridiem }}
        </native:text>
    </native:column>
</native:row>
