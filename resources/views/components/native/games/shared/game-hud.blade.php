@use('Nativephp\NativeUi\Theme')
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
])

{{-- Top HUD, Elevate-style: a close control and the live score on the left; a
     row of heart lives with the round counter beneath on the right. Shared by
     both games, so lives read the same everywhere. --}}
@php
    $tokens = Theme::all();
    $fullLight = data_get($tokens, 'light.danger') ?? config('native-ui.theme.light.danger', '#C53637');
    $fullDark = data_get($tokens, 'dark.danger') ?? config('native-ui.theme.dark.danger', '#F2716A');
    $emptyLight = data_get($tokens, 'light.muted-text') ?? config('native-ui.theme.light.muted-text', '#90909A');
    $emptyDark = data_get($tokens, 'dark.muted-text') ?? config('native-ui.theme.dark.muted-text', '#6B6B74');
@endphp

<native:row class="w-full items-start justify-between">
    <native:row class="items-center gap-2">
        <native:pressable
            @press="exit"
            a11y-label="Close game"
            :press-scale="0.9"
            class="w-9 h-9 items-center justify-center rounded-full bg-theme-surface"
        >
            <x-native.ui.icon :ios="Ios::Xmark" :android="Android::Close" :size="16" />
        </native:pressable>

        @if ($combo >= 2)
            <native:column
                native:key="combo-{{ $combo }}"
                class="rounded-full bg-theme-primary-surface px-2 py-1"
                :scale="1.08"
                :animate-duration="$motionDuration"
                animate-easing="ease-out"
                a11y-label="Combo times {{ $combo }}"
            >
                <native:text class="text-[11] font-bold text-theme-accent">×{{ $combo }}</native:text>
            </native:column>
        @endif

        <native:text
            native:key="score-{{ $score }}"
            class="text-[17] font-bold text-theme-primary-text"
            :scale="$motionDuration > 0 ? 1.06 : 1"
            :animate-duration="$motionDuration"
            animate-easing="ease-out"
        >
            {{ number_format($score) }}
        </native:text>
    </native:row>

    <native:column class="items-end gap-1">
        <native:row class="items-center gap-1" a11y-label="{{ $lives }} of {{ $maxLives }} lives remaining">
            @for ($life = 1; $life <= $maxLives; $life++)
                @php $isFull = $life <= $lives; @endphp
                <native:column native:key="heart-{{ $life }}-{{ $isFull ? 'full' : 'empty' }}">
                    <x-native.ui.icon
                        :ios="$isFull ? Ios::HeartFill : Ios::Heart"
                        :android="$isFull ? Android::Favorite : Android::FavoriteBorder"
                        :size="18"
                        :color="$isFull ? $fullLight : $emptyLight"
                        :dark-color="$isFull ? $fullDark : $emptyDark"
                    />
                </native:column>
            @endfor
        </native:row>

        @if ($total > 0)
            <native:text class="text-[10] font-semibold uppercase tracking-widest text-theme-muted-text">
                Round {{ $round }} / {{ $total }}
            </native:text>
        @endif
    </native:column>
</native:row>
