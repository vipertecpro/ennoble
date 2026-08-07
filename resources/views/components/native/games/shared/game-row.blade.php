@use('App\Icons\Ios')
@use('App\Icons\AndroidOutlined')
@use('App\NativeUI\Tokens\Gradients')

{{--
    Wide game row (Games "All games"): identity-gradient glyph chip, name +
    meta, and a play affordance. Fills the width and stacks densely.
--}}
@props([
    'game',
    'meta' => null,
])

@php
    [$ios, $android] = match ($game['slug']) {
        'word-match' => [Ios::TextformatAbc, AndroidOutlined::Abc],
        'quick-math' => [Ios::NumberSquare, AndroidOutlined::Numbers],
        'recall' => [Ios::Grid, AndroidOutlined::GridView],
        'flow' => [Ios::Waveform, AndroidOutlined::Waves],
        'signal' => [Ios::Paintpalette, AndroidOutlined::Palette],
        default => [Ios::Gamecontroller, AndroidOutlined::SportsEsports],
    };
    $best = $game['best_score'] ?? null;
    $line = $meta ?? ($best === null ? 'New · not played yet' : 'Best '.number_format($best));
    $hasLottie = is_file(resource_path("animations/{$game['slug']}.json"));
@endphp

<native:pressable
    class="w-full rounded-2xl p-3 border bg-theme-surface {{ Gradients::hairline() }}"
    :press-scale="0.98"
    a11y-label="Play {{ $game['title'] }}"
    @press="openGame('{{ $game['slug'] }}')"
>
    <native:row class="w-full items-center gap-3">
        @if ($hasLottie)
            <native:column class="w-12 h-12 items-center justify-center rounded-xl bg-theme-surface-variant">
                <native:lottie-player source="{{ $game['slug'] }}" loop class="w-9 h-9" alt="{{ $game['title'] }}" />
            </native:column>
        @else
            <native:column class="w-12 h-12 items-center justify-center rounded-xl {{ Gradients::gameSolid($game['slug']) }}">
                <native:icon :ios="$ios" :android="$android" :size="24" class="text-black" />
            </native:column>
        @endif
        <native:column class="flex-1 gap-0.5">
            <native:text class="text-[15] font-semibold text-theme-primary-text">{{ $game['title'] }}</native:text>
            <native:text class="text-[12] text-theme-muted-text">{{ $line }}</native:text>
        </native:column>
        <native:column class="w-9 h-9 items-center justify-center rounded-xl {{ Gradients::cta() }}">
            <native:icon :ios="Ios::PlayFill" :android="AndroidOutlined::PlayArrow" :size="16" class="text-black" />
        </native:column>
    </native:row>
</native:pressable>
