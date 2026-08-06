@use('App\Icons\Ios')
@use('App\Icons\AndroidOutlined')
@use('App\NativeUI\Tokens\Gradients')

{{--
    Carousel game card (Home "Your games"): a compact tile with the game's
    identity gradient, glyph, name, and best score. Taps through to the game.
--}}
@props([
    'game',
])

@php
    [$ios, $android] = match ($game['slug']) {
        'word-match' => [Ios::TextformatAbc, AndroidOutlined::Abc],
        'quick-math' => [Ios::NumberSquare, AndroidOutlined::Numbers],
        'recall' => [Ios::Grid, AndroidOutlined::GridView],
        'flow' => [Ios::Waveform, AndroidOutlined::Waves],
        default => [Ios::Gamecontroller, AndroidOutlined::SportsEsports],
    };
    $hue = Gradients::gameHue($game['slug']);
    $best = $game['best_score'] ?? null;
@endphp

<native:pressable
    class="w-32 items-start gap-2.5 rounded-2xl p-3 border {{ Gradients::gameGlass($game['slug']) }} {{ Gradients::gameBorder($game['slug']) }}"
    :press-scale="0.97"
    a11y-label="{{ $game['title'] }}"
    @press="openGame('{{ $game['slug'] }}')"
>
    <native:column class="w-10 h-10 items-center justify-center rounded-xl bg-{{ $hue }}/15">
        <native:icon :ios="$ios" :android="$android" :size="22" class="text-{{ $hue }}" />
    </native:column>
    <native:column class="w-full gap-0.5">
        <native:text class="text-[13] font-semibold text-theme-primary-text">{{ $game['title'] }}</native:text>
        <native:text class="text-[10.5] text-theme-muted-text">{{ $best === null ? 'New' : 'Best '.number_format($best) }}</native:text>
    </native:column>
</native:pressable>
