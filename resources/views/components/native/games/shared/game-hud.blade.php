@use('Native\Mobile\UI\Theme')
@use('App\Icons\Ios')
@use('App\Icons\Android')

@props([
    'lives' => 3,
    'maxLives' => 3,
    'score' => 0,
    'combo' => 0,
    'round' => 0,
    'total' => 0,
    'motionDuration' => 0,
    // Some games have no lives to show. Stack, for instance, sweeps the board
    // instead of ending a run, so a row of hearts describes nothing the player
    // is tracking.
    'showLives' => true,
])

{{-- Shared in-game top bar. A clear Back control (leaves the game from any
     phase), the live score and combo pill, then the lives row + round counter.

     The controls are flattened into ONE row separated by a growing spacer,
     rather than a `justify-between` row wrapping a nested left row: on iOS the
     flex engine measures a row's children with an unbounded width proposal, so
     a nested row along that axis collapses to zero and its contents vanish (the
     lives column survived because a column sizes its cross-axis). A spacer keeps
     both sides content-sized and reliably visible on both platforms.

     Every colour is a theme token — with explicit light/dark pairs on the raw
     icon colour props — so the HUD reads with proper contrast in both modes. --}}
@php
    $tokens = Theme::all();
    $backLight = data_get($tokens, 'light.on-surface-variant', '#55555E');
    $backDark = data_get($tokens, 'dark.on-surface-variant', '#A4A4AD');
    $fullLight = data_get($tokens, 'light.danger', '#C53637');
    $fullDark = data_get($tokens, 'dark.danger', '#F2716A');
    $emptyLight = data_get($tokens, 'light.outline', '#C9C9CE');
    $emptyDark = data_get($tokens, 'dark.outline', '#3A3A40');
@endphp

<native:row class="w-full items-center gap-3">
    <native:pressable
        @press="exit"
        a11y-label="Back to games"
        a11y-hint="Leaves this game"
        :press-scale="0.9"
        class="w-10 h-10 items-center justify-center rounded-full bg-theme-surface-variant border border-rose-500/25 shadow-lg"
    >
        <x-native.ui.icon
            :ios="Ios::ChevronLeft"
            :android="Android::ArrowBack"
            :size="18"
            :color="$backLight"
            :dark-color="$backDark"
        />
    </native:pressable>

    @if ($combo >= 2)
        {{-- The pill keeps ONE identity for the whole combo run, so the
             number rolls inside it instead of the pill being destroyed and
             rebuilt on every increment. Spring, not ease-out: a combo is a
             moment of momentum and should overshoot slightly before it
             settles. --}}
        <native:column
            native:key="combo-pill"
            class="rounded-full bg-linear-to-r from-rose-500/30 to-orange-400/20 border border-rose-500/40 shadow-lg px-2.5 py-1"
            :scale="1.08"
            :animate-duration="$motionDuration"
            animate-easing="spring"
            a11y-label="Combo times {{ $combo }}"
        >
            <native:text class="text-[12] font-bold text-theme-accent" content-transition="numeric">×{{ $combo }}</native:text>
        </native:column>
    @endif

    {{-- Keyed by ROLE, never by value. Keying a number by its own value
         changes the key on every change, which makes the renderer destroy the
         view and build a new one — the digits cannot roll between two
         different views. This one line is the difference between a score that
         counts up and one that blinks. --}}
    <native:text
        native:key="hud-score"
        class="text-[18] font-bold text-theme-primary-text"
        content-transition="numeric"
        :animate-duration="$motionDuration"
        animate-easing="spring"
    >
        {{ number_format($score) }}
    </native:text>

    <native:spacer class="flex-1" />

    <native:column class="items-end gap-1">
        @if ($showLives)
        <native:row class="items-center gap-2" a11y-label="{{ $lives }} of {{ $maxLives }} lives remaining">
            @for ($life = 1; $life <= $maxLives; $life++)
                @php $isFull = $life <= $lives; @endphp
                <native:column native:key="heart-{{ $life }}-{{ $isFull ? 'full' : 'empty' }}">
                    <x-native.ui.icon
                        :ios="$isFull ? Ios::HeartFill : Ios::Heart"
                        :android="$isFull ? Android::Favorite : Android::FavoriteBorder"
                        :size="20"
                        :color="$isFull ? $fullLight : $emptyLight"
                        :dark-color="$isFull ? $fullDark : $emptyDark"
                    />
                </native:column>
            @endfor
        </native:row>
        @endif

        @if ($total > 0)
            <native:text class="text-[10] font-semibold uppercase tracking-widest text-theme-muted-text">
                Round {{ $round }} / {{ $total }}
            </native:text>
        @endif
    </native:column>
</native:row>
